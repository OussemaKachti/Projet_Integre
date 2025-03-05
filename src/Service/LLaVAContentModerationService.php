<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class LLaVAContentModerationService
{
    private $httpClient;
    private $huggingFaceApiKey;
    private $logger;
    private $modelEndpoint;
    private $cache;
    private $nsfwModelEndpoint;

    public function __construct(
        HttpClientInterface $httpClient,
        string $huggingFaceApiKey,
        LoggerInterface $logger,
        string $modelEndpoint = 'llava-hf/llava-1.5-7b-hf',
        CacheInterface $cache = null,
        string $nsfwModelEndpoint = 'Falconsai/nsfw_image_detection'
    ) {
        $this->httpClient = $httpClient;
        $this->huggingFaceApiKey = $huggingFaceApiKey;
        $this->logger = $logger;
        $this->modelEndpoint = $modelEndpoint;
        $this->cache = $cache;
        $this->nsfwModelEndpoint = $nsfwModelEndpoint;
    }

    /**
     * Check if a profile picture contains inappropriate content
     */
   /**
 * Check if a profile picture contains inappropriate content
 */
public function checkProfilePicture(string $imagePath): array
{
    // Verify API key is configured
    if (empty($this->huggingFaceApiKey) || $this->huggingFaceApiKey === '%env(HUGGINGFACE_API_KEY)%') {
        $this->logger->critical('Missing or invalid Hugging Face API key');
        return [
            'is_inappropriate' => true, // CHANGED: Fail closed for security
            'explanation' => 'Content moderation service misconfigured',
            'success' => false,
            'error' => 'Invalid API key configuration'
        ];
    }

    try {
        // Read the image file directly
        $imageContent = file_get_contents($imagePath);
        $base64Image = base64_encode($imageContent);
        
        // Log image details for debugging
        $this->logger->debug('Processing image for moderation', [
            'image_path' => $imagePath,
            'image_size_kb' => round(strlen($imageContent) / 1024, 2),
            'base64_length' => strlen($base64Image)
        ]);

        // Prepare the prompt for LLaVA
        $prompt = "Analyze this image and determine if it contains any inappropriate content including: 
                  sexual imagery, nudity, offensive gestures, hate symbols, violence, 
                  blood/gore, weapons, or any content unsuitable for a professional website. 
                  Please respond with ONLY 'YES' if inappropriate or 'NO' if appropriate, 
                  followed by a brief explanation.";

        // Log API request
        $this->logger->debug('Sending request to Hugging Face API', [
            'model' => $this->modelEndpoint,
            'prompt' => $prompt
        ]);

        // Make API request with retry logic
        $result = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->modelEndpoint}", [
            'inputs' => [
                'image' => $base64Image,
                'prompt' => $prompt,
            ],
        ]);
        
        // Log the ENTIRE response for debugging
        $this->logger->debug('Raw LLaVA API response', [
            'full_response' => json_encode($result)
        ]);

        // Check for error in response
        if (isset($result['error'])) {
            $this->logger->warning('LLaVA API returned error', [
                'error' => $result['error']
            ]);
            
            // CHANGED: Don't rely on LLaVA if it returns an error,
            // instead defer to the NSFW model result
            return [
                'is_inappropriate' => false, // Let the NSFW model decide
                'explanation' => 'LLaVA moderation unavailable: ' . $result['error'],
                'success' => false,
                'error' => $result['error']
            ];
        }

        // Extract generated text
        $generatedText = $this->extractGeneratedText($result);
        
        $this->logger->info('LLaVA image check response', ['text' => $generatedText]);

        // Check for empty response
        if (empty(trim($generatedText))) {
            $this->logger->warning('Empty response from LLaVA API');
            // CHANGED: Don't rely on LLaVA if it returns empty response
            return [
                'is_inappropriate' => false, // Let the NSFW model decide
                'explanation' => 'LLaVA moderation returned empty result',
                'success' => false
            ];
        }

        // Basic check for YES at the beginning of the response
        $isInappropriate = (preg_match('/^yes\b/i', $generatedText) === 1);
        
        $this->logger->debug('LLaVA moderation decision', [
            'is_inappropriate' => $isInappropriate,
            'text' => $generatedText
        ]);

        return [
            'is_inappropriate' => $isInappropriate,
            'explanation' => $generatedText,
            'success' => true,
        ];
    } catch (\Exception $e) {
        $this->logger->error('LLaVA image check failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // CHANGED: Don't rely on LLaVA if it throws an exception,
        // instead defer to the NSFW model result
        return [
            'is_inappropriate' => false, // Let the NSFW model decide
            'explanation' => 'LLaVA moderation service unavailable: ' . $e->getMessage(),
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
    /**
     * Check image with specialized NSFW detection model
     */
    /**
 * Check image with specialized NSFW detection model
 */
public function checkImageWithNSFWModel(string $imagePath): array
{
    // Verify API key is configured
    if (empty($this->huggingFaceApiKey) || $this->huggingFaceApiKey === '%env(HUGGINGFACE_API_KEY)%') {
        $this->logger->critical('Missing or invalid Hugging Face API key');
        return [
            'is_inappropriate' => true, // CHANGED: Fail closed for safety
            'explanation' => 'Content moderation service misconfigured',
            'success' => false,
            'error' => 'Invalid API key configuration'
        ];
    }

    try {
        // Read image directly
        $imageContent = file_get_contents($imagePath);
        $base64Image = base64_encode($imageContent);
        
        // Make API request with retry logic
        $result = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->nsfwModelEndpoint}", [
            'inputs' => $base64Image,
        ]);
        
        // Log the entire response structure for debugging
        $this->logger->debug('NSFW model raw response', [
            'result' => json_encode($result),
            'result_type' => gettype($result)
        ]);
        
        // Default to rejecting if we can't properly parse the response
        $isInappropriate = true; // CHANGED: Fail closed by default
        $explanation = "Could not properly interpret content moderation results";
        $scores = [];
        
        // FIXED LOGIC: Process the result based on its structure
        if (is_array($result)) {
            // Case 1: Direct array of classifications
            if (!empty($result) && isset($result[0]) && is_array($result[0]) && isset($result[0]['label'])) {
                $this->logger->debug('Processing response format: array of classifications');
                
                $scores = $result;
                $highestCategory = '';
                $highestScore = 0;
                $isInappropriate = false; // Start with assumption it's appropriate
                
                // Loop through all categories
                foreach ($result as $category) {
                    if (isset($category['label']) && isset($category['score'])) {
                        $this->logger->debug('Checking category', [
                            'label' => $category['label'],
                            'score' => $category['score']
                        ]);
                        
                        // Track highest score category
                        if ($category['score'] > $highestScore) {
                            $highestScore = $category['score'];
                            $highestCategory = $category['label'];
                        }
                        
                        // Check if this is an NSFW category
                        $label = strtolower($category['label']);
                        $nsfw_categories = ['nsfw', 'porn', 'sexy', 'hentai', 'explicit', 'nudity'];
                        
                        if (in_array($label, $nsfw_categories) && $category['score'] > 0.5) {
                            $isInappropriate = true;
                            $explanation = "Content detected as {$category['label']} with " . 
                                round($category['score'] * 100, 2) . "% confidence";
                                
                            $this->logger->info('NSFW content detected', [
                                'category' => $category['label'],
                                'score' => $category['score']
                            ]);
                        }
                    }
                }
                
                if (!$isInappropriate && !empty($highestCategory)) {
                    $explanation = "Content categorized as {$highestCategory} with " . 
                        round($highestScore * 100, 2) . "% confidence";
                }
            }
            // Case 2: Single label format
            else if (isset($result['label']) && isset($result['score'])) {
                $this->logger->debug('Processing response format: single label object');
                
                $label = strtolower($result['label']);
                $nsfw_categories = ['nsfw', 'porn', 'sexy', 'hentai', 'explicit', 'nudity'];
                
                if (in_array($label, $nsfw_categories) && $result['score'] > 0.5) {
                    $isInappropriate = true;
                    $explanation = "Content detected as {$result['label']} with " . 
                        round($result['score'] * 100, 2) . "% confidence";
                } else {
                    $isInappropriate = false;
                    $explanation = "Content categorized as {$result['label']} with " . 
                        round($result['score'] * 100, 2) . "% confidence";
                }
                
                $scores = [$result];
            }
            // Case 3: Error response from API
            else if (isset($result['error'])) {
                $this->logger->error('API returned error', [
                    'error' => $result['error']
                ]);
                
                $isInappropriate = true; // CHANGED: Fail closed on error
                $explanation = "Moderation service error: " . $result['error'];
            }
        }
        
        return [
            'is_inappropriate' => $isInappropriate,
            'explanation' => $explanation,
            'success' => true,
            'scores' => $scores
        ];
        
    } catch (\Exception $e) {
        $this->logger->error('NSFW image check failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        
        // CHANGED: Fail closed for safety
        return [
            'is_inappropriate' => true,
            'explanation' => 'Content moderation service unavailable, image rejected for safety',
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}
    /**
     * Check if text contains inappropriate content
     */
    public function checkUserText(string $text): array
    {
        // Skip empty text
        if (empty(trim($text))) {
            return [
                'is_inappropriate' => false,
                'explanation' => 'No content to moderate',
                'success' => true,
            ];
        }

        // Use cache if available
        if ($this->cache) {
            $cacheKey = 'text_moderation_' . md5($text);
            
            return $this->cache->get($cacheKey, function (ItemInterface $item) use ($text) {
                $item->expiresAfter(86400); // Cache for 24 hours
                return $this->performTextCheck($text);
            });
        }
        
        return $this->performTextCheck($text);
    }

    /**
     * Perform the actual text check
     */
    private function performTextCheck(string $text): array
    {
        try {
            // Verify API key is configured
            if (empty($this->huggingFaceApiKey) || $this->huggingFaceApiKey === '%env(HUGGINGFACE_API_KEY)%') {
                $this->logger->critical('Missing or invalid Hugging Face API key');
                return [
                    'is_inappropriate' => false,
                    'explanation' => 'Content moderation service misconfigured',
                    'success' => false,
                    'error' => 'Invalid API key configuration'
                ];
            }

            // Use a text classification model instead of LLaVA for text
            $textModel = "facebook/bart-large-mnli";
            
            // Make API request with retry logic
            $result = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$textModel}", [
                'inputs' => $text,
                'parameters' => [
                    'candidate_labels' => ["appropriate", "inappropriate", "offensive", "harmful"]
                ]
            ], 10);
            
            $this->logger->info('Text classification result', [
                'result' => json_encode($result)
            ]);
            
            // Check if any inappropriate label has high score
            $isInappropriate = false;
            $explanation = "Content appears appropriate.";
            
            if (isset($result['labels']) && isset($result['scores']) && is_array($result['labels']) && is_array($result['scores'])) {
                foreach ($result['labels'] as $index => $label) {
                    if (isset($result['scores'][$index]) && $label !== "appropriate" && $result['scores'][$index] > 0.6) {
                        $isInappropriate = true;
                        $explanation = "Content detected as {$label} with confidence score: " . round($result['scores'][$index] * 100, 2) . "%";
                        break;
                    }
                }
            }

            return [
                'is_inappropriate' => $isInappropriate,
                'explanation' => $explanation,
                'success' => true,
                'scores' => $result['scores'] ?? []
            ];
        } catch (\Exception $e) {
            $this->logger->error('Text check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'is_inappropriate' => false, // Fail open for text, unlike images
                'explanation' => 'Content moderation service unavailable',
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make API request with retry logic
     */
    private function makeApiRequest(string $endpoint, array $payload, int $timeout = 30, int $maxRetries = 3): array
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                $response = $this->httpClient->request('POST', $endpoint, [
                    'headers' => [
                        'Authorization' => "Bearer {$this->huggingFaceApiKey}",
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                    'timeout' => $timeout,
                ]);
                
                // Get the response content as a string first
                $responseContent = $response->getContent(false);
                
                // Log the raw response content
                $this->logger->debug('Raw API response content', [
                    'content' => $responseContent
                ]);
                
                // Try to decode JSON
                try {
                    return json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    // If JSON decode fails, return a simple array with the content
                    $this->logger->warning('JSON decode failed', [
                        'error' => $e->getMessage(),
                        'content' => $responseContent
                    ]);
                    return ['raw_content' => $responseContent];
                }
            } catch (\Exception $e) {
                $attempt++;
                
                // Log the error
                $this->logger->warning('API request failed, attempt ' . $attempt, [
                    'error' => $e->getMessage(),
                    'endpoint' => $endpoint
                ]);
                
                // If it's the last attempt, rethrow
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                
                // Exponential backoff
                sleep(pow(2, $attempt));
            }
        }
        
        // This should never happen due to the throw above, but just in case
        throw new \Exception('API request failed after ' . $maxRetries . ' attempts');
    }

    /**
     * Extract generated text from various API response formats
     */
    private function extractGeneratedText($result): string
    {
        // Be very defensive about response format
        if (is_array($result)) {
            if (isset($result[0]['generated_text'])) {
                return $result[0]['generated_text'];
            } 
            
            if (isset($result['generated_text'])) {
                return $result['generated_text'];
            }
            
            if (isset($result[0]) && is_string($result[0])) {
                return $result[0];
            }
            
            if (isset($result['raw_content']) && is_string($result['raw_content'])) {
                return $result['raw_content'];
            }
            
            // Try to extract any string we can find
            foreach ($result as $key => $value) {
                if (is_string($value)) {
                    return $value;
                }
                if (is_array($value) && isset($value[0]) && is_string($value[0])) {
                    return $value[0];
                }
            }
        }
        
        if (is_string($result)) {
            return $result;
        }
        
        // Last resort, convert to JSON string
        return 'Unparseable response: ' . json_encode($result);
    }
}