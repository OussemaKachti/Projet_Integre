<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class ContentModerationService
{
    private $httpClient;
    private $huggingFaceApiKey;
    private $logger;
    private $cache;
    
    // Models for specific inappropriate content categories
    private $nsfwModel;
    private $violenceModel; // Will use Dabid/abusive-tagalog-profanity-detection
    private $hateSpeechModel;
    private $captionModel;
    
    // Content categories and their descriptions
    private const CONTENT_CATEGORIES = [
        'sexual_content' => 'Sexual imagery or nudity',
        'violence' => 'Violence or threatening content',
        'hate_symbols' => 'Hate symbols or extremist content', 
        'weapons' => 'Weapons or dangerous items',
        'blood_gore' => 'Blood, gore or graphic medical content',
        'offensive_gestures' => 'Offensive gestures or inappropriate behavior'
    ];
    
    // Critical caption keywords - minimal set focused on visual content detection
    // These are terms that if found in image captions should trigger additional scrutiny
    private const CRITICAL_CAPTION_TERMS = [
        'blood_gore' => ['blood', 'bloody', 'gore', 'guts', 'wound', 'injury', 'dead body', 'corpse'],
        'weapons' => ['gun', 'rifle', 'pistol', 'knife', 'weapon', 'shooting'],
        'violence' => ['fight', 'hitting', 'punching', 'kicking', 'injured'],
        'offensive_gestures' => ['middle finger']
    ];

    public function __construct(
        HttpClientInterface $httpClient,
        string $huggingFaceApiKey,
        LoggerInterface $logger,
        CacheInterface $cache = null,
        string $nsfwModel = 'Falconsai/nsfw_image_detection',
        string $violenceModel = 'Dabid/abusive-tagalog-profanity-detection',
        string $hateSpeechModel = 'facebook/bart-large-mnli',
        string $captionModel = 'Salesforce/blip-image-captioning-base'
    ) {
        $this->httpClient = $httpClient;
        $this->huggingFaceApiKey = $huggingFaceApiKey;
        $this->logger = $logger;
        $this->cache = $cache;
        
        $this->nsfwModel = $nsfwModel;
        $this->violenceModel = $violenceModel;
        $this->hateSpeechModel = $hateSpeechModel;
        $this->captionModel = $captionModel;
        
        $this->logger->info('Content Moderation Service initialized with categorization');
    }

    /**
     * Check if an image contains inappropriate content with detailed categorization
     */
    public function checkImageContent(string $imagePath): array
    {
        try {
            // Read the image file
            $imageContent = file_get_contents($imagePath);
            $base64Image = base64_encode($imageContent);
            
            $this->logger->debug('Processing image for categorized moderation', [
                'image_path' => $imagePath,
                'image_size_kb' => round(strlen($imageContent) / 1024, 2)
            ]);
            
            // Initialize result structure with all categories
            $categories = [];
            foreach (self::CONTENT_CATEGORIES as $category => $description) {
                $categories[$category] = [
                    'detected' => false,
                    'confidence' => 0,
                    'description' => $description
                ];
            }
            
            // Step 1: Check for NSFW content (sexual/nudity)
            try {
                $nsfwResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->nsfwModel}", [
                    'inputs' => $base64Image
                ]);
                
                $this->processNSFWResults($nsfwResult, $categories);
            } catch (\Exception $e) {
                $this->logger->warning('NSFW model check failed', [
                    'error' => $e->getMessage()
                ]);
                // Continue with other checks
            }
            
            // Step 2: Get image caption for context
            $caption = '';
            try {
                $captionResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->captionModel}", [
                    'inputs' => $base64Image
                ]);
                
                $caption = $this->extractCaption($captionResult);
                $this->logger->debug('Image caption generated', [
                    'caption' => $caption
                ]);
                
                // Step 3: Use caption for additional detection
                if (!empty($caption)) {
                    // Added: Direct check for critical content in captions
                    $this->analyzeCriticalCaptionTerms($caption, $categories);
                    
                    // Check caption for violence and toxicity using the Tagalog profanity model
                    try {
                        $violenceResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->violenceModel}", [
                            'inputs' => $caption
                        ]);
                        
                        $this->processProfanityResults($violenceResult, $categories);
                    } catch (\Exception $e) {
                        $this->logger->warning('Violence model (Tagalog) failed', [
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    // Check for hate speech in caption
                    try {
                        $hateResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->hateSpeechModel}", [
                            'inputs' => $caption,
                            'parameters' => [
                                'candidate_labels' => ["appropriate", "hate speech", "offensive", "extremist content"]
                            ]
                        ]);
                        
                        $this->processHateSpeechResults($hateResult, $categories);
                    } catch (\Exception $e) {
                        $this->logger->warning('Hate speech detection failed', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning('Image captioning failed', [
                    'error' => $e->getMessage()
                ]);
                // If caption fails, we still have the NSFW check
            }
            
            // Step 4: Generate final response
            $detectedCategories = [];
            $anyInappropriate = false;
            
            foreach ($categories as $category => $data) {
                if ($data['detected']) {
                    $detectedCategories[] = $data['description'] . ' (' . round($data['confidence'] * 100) . '% confidence)';
                    $anyInappropriate = true;
                }
            }
            
            // Format response according to the required prompt style
            if ($anyInappropriate) {
                $explanation = "YES. The image contains " . implode(', ', $detectedCategories) . ".";
            } else {
                $explanation = "NO. The image appears to be appropriate for a professional website.";
            }
            
            return [
                'is_inappropriate' => $anyInappropriate,
                'explanation' => $explanation,
                'categories' => $categories,
                'caption' => $caption ?? '',
                'success' => true
            ];
            
        } catch (\Exception $e) {
            $this->logger->error('Categorized image check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Try the offline fallback
            $offlineResult = $this->offlineImageCheck($imagePath);
            if ($offlineResult['success']) {
                return $offlineResult;
            }
            
            return [
                'is_inappropriate' => false, // Fail open since we're testing
                'explanation' => 'Error analyzing image: ' . $e->getMessage(),
                'success' => false
            ];
        }
    }

    /**
     * Check user text for inappropriate content using only models
     */
    public function checkUserText(string $text): array
    {
        $this->logger->debug('Checking user text for inappropriate content', [
            'text_length' => strlen($text)
        ]);
        
        try {
            // Initialize categories with same structure as image checks
            $categories = [];
            foreach (self::CONTENT_CATEGORIES as $category => $description) {
                $categories[$category] = [
                    'detected' => false,
                    'confidence' => 0,
                    'description' => $description
                ];
            }
            
            $modelsWorked = false;
            
            // 1. Check with abusive Tagalog profanity model - primary check
            try {
                $profanityResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->violenceModel}", [
                    'inputs' => $text
                ]);
                
                $this->processProfanityResults($profanityResult, $categories);
                $modelsWorked = true;
            } catch (\Exception $e) {
                $this->logger->warning('Tagalog profanity detection failed', [
                    'error' => $e->getMessage()
                ]);
                // Try other models
            }
            
            // 2. Check with hate speech model
            try {
                $hateResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->hateSpeechModel}", [
                    'inputs' => $text,
                    'parameters' => [
                        'candidate_labels' => ["appropriate", "hate speech", "offensive", "extremist content"]
                    ]
                ]);
                
                $this->processHateSpeechResults($hateResult, $categories);
                $modelsWorked = true;
            } catch (\Exception $e) {
                $this->logger->warning('Hate speech detection failed', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // If no models succeeded, fail safely
            if (!$modelsWorked) {
                $this->logger->error('All text moderation models failed');
                return [
                    'is_inappropriate' => false, // Fail open for testing
                    'explanation' => 'Text moderation unavailable at this time',
                    'success' => false
                ];
            }
            
            // 3. Generate final response
            $detectedCategories = [];
            $anyInappropriate = false;
            
            foreach ($categories as $category => $data) {
                if ($data['detected']) {
                    $detectedCategories[] = $data['description'] . ' (' . round($data['confidence'] * 100) . '% confidence)';
                    $anyInappropriate = true;
                }
            }
            
            if ($anyInappropriate) {
                $explanation = "The text contains inappropriate content: " . implode(', ', $detectedCategories);
            } else {
                $explanation = "The text appears to be appropriate";
            }
            
            return [
                'is_inappropriate' => $anyInappropriate,
                'explanation' => $explanation,
                'categories' => $categories,
                'success' => true
            ];
        } catch (\Exception $e) {
            $this->logger->error('Text content check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return appropriate since we're testing
            return [
                'is_inappropriate' => false,
                'explanation' => 'Model analysis failed: ' . $e->getMessage(),
                'success' => false
            ];
        }
    }
    
    /**
     * Analyze caption for critical terms that indicate inappropriate content
     * This is specifically targeted at visual content that might be missed by sentiment classifiers
     */
    private function analyzeCriticalCaptionTerms(string $caption, array &$categories): void
    {
        $caption = strtolower($caption);
        
        foreach (self::CRITICAL_CAPTION_TERMS as $category => $terms) {
            foreach ($terms as $term) {
                if (strpos($caption, $term) !== false) {
                    $categories[$category]['detected'] = true;
                    $categories[$category]['confidence'] = 0.85; // High confidence when caption explicitly mentions a critical term
                    
                    $this->logger->info("Critical content detected in image caption", [
                        'category' => $category,
                        'term' => $term,
                        'caption' => $caption
                    ]);
                    
                    // No need to check more terms in this category
                    break;
                }
            }
        }
    }
    
    /**
     * Lightweight image check using just the NSFW model
     * This is more efficient than the full check but less comprehensive
     */
    public function checkImageWithNSFWModel(string $imagePath): array
    {
        try {
            // Read the image file
            $imageContent = file_get_contents($imagePath);
            $base64Image = base64_encode($imageContent);
            
            $this->logger->debug('Processing image with lightweight NSFW-only check', [
                'image_path' => $imagePath,
                'image_size_kb' => round(strlen($imageContent) / 1024, 2)
            ]);
            
            // Use cache if available
            $cacheKey = 'nsfw_check_' . md5($base64Image);
            
            if ($this->cache) {
                return $this->cache->get($cacheKey, function (ItemInterface $item) use ($base64Image, $imagePath) {
                    $item->expiresAfter(3600); // Cache for 1 hour
                    
                    // Check for NSFW content (sexual/nudity)
                    $nsfwResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->nsfwModel}", [
                        'inputs' => $base64Image
                    ]);
                    
                    $nsfwScore = 0;
                    
                    if (!empty($nsfwResult) && is_array($nsfwResult)) {
                        foreach ($nsfwResult as $entry) {
                            if (isset($entry['label']) && isset($entry['score'])) {
                                if (strtolower($entry['label']) === 'nsfw' && $entry['score'] > $nsfwScore) {
                                    $nsfwScore = $entry['score'];
                                }
                            }
                        }
                    }
                    
                    $isInappropriate = $nsfwScore > 0.6; // Using a slightly higher threshold for profile pics
                    
                    // If not inappropriate by NSFW standards, check caption for critical terms
                    if (!$isInappropriate) {
                        try {
                            // Get image caption
                            $captionResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->captionModel}", [
                                'inputs' => $base64Image
                            ]);
                            
                            $caption = $this->extractCaption($captionResult);
                            
                            // Initialize categories
                            $categories = [];
                            foreach (self::CONTENT_CATEGORIES as $category => $description) {
                                $categories[$category] = [
                                    'detected' => false,
                                    'confidence' => 0,
                                    'description' => $description
                                ];
                            }
                            
                            // Check for critical terms in caption
                            $this->analyzeCriticalCaptionTerms($caption, $categories);
                            
                            // Check if any categories were detected
                            foreach ($categories as $category => $data) {
                                if ($data['detected']) {
                                    $isInappropriate = true;
                                    $explanation = "YES. The image contains " . $data['description'] . 
                                                   " (detected from caption: \"" . $caption . "\")";
                                    
                                    return [
                                        'is_inappropriate' => true,
                                        'explanation' => $explanation,
                                        'caption' => $caption,
                                        'nsfw_score' => $nsfwScore,
                                        'categories' => $categories,
                                        'success' => true
                                    ];
                                }
                            }
                        } catch (\Exception $e) {
                            // If caption fails, continue with NSFW result only
                            $this->logger->warning('Caption-based check failed in NSFW model', [
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    
                    if ($isInappropriate) {
                        $explanation = "YES. The image contains inappropriate content (NSFW) with " . 
                                       round($nsfwScore * 100) . "% confidence.";
                    } else {
                        $explanation = "NO. The image appears to be appropriate.";
                    }
                    
                    return [
                        'is_inappropriate' => $isInappropriate,
                        'explanation' => $explanation,
                        'nsfw_score' => $nsfwScore,
                        'success' => true
                    ];
                });
            } else {
                // Direct API call if no cache - same logic as above but without caching
                $nsfwResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->nsfwModel}", [
                    'inputs' => $base64Image
                ]);
                
                $nsfwScore = 0;
                
                if (!empty($nsfwResult) && is_array($nsfwResult)) {
                    foreach ($nsfwResult as $entry) {
                        if (isset($entry['label']) && isset($entry['score'])) {
                            if (strtolower($entry['label']) === 'nsfw' && $entry['score'] > $nsfwScore) {
                                $nsfwScore = $entry['score'];
                            }
                        }
                    }
                }
                
                $isInappropriate = $nsfwScore > 0.6;
                
                // If not inappropriate by NSFW standards, check caption for critical terms
                if (!$isInappropriate) {
                    try {
                        // Get image caption
                        $captionResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->captionModel}", [
                            'inputs' => $base64Image
                        ]);
                        
                        $caption = $this->extractCaption($captionResult);
                        
                        // Initialize categories
                        $categories = [];
                        foreach (self::CONTENT_CATEGORIES as $category => $description) {
                            $categories[$category] = [
                                'detected' => false,
                                'confidence' => 0,
                                'description' => $description
                            ];
                        }
                        
                        // Check for critical terms in caption
                        $this->analyzeCriticalCaptionTerms($caption, $categories);
                        
                        // Check if any categories were detected
                        foreach ($categories as $category => $data) {
                            if ($data['detected']) {
                                $isInappropriate = true;
                                $explanation = "YES. The image contains " . $data['description'] . 
                                               " (detected from caption: \"" . $caption . "\")";
                                
                                return [
                                    'is_inappropriate' => true,
                                    'explanation' => $explanation,
                                    'caption' => $caption,
                                    'nsfw_score' => $nsfwScore,
                                    'categories' => $categories,
                                    'success' => true
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        // If caption fails, continue with NSFW result only
                        $this->logger->warning('Caption-based check failed in NSFW model', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                if ($isInappropriate) {
                    $explanation = "YES. The image contains inappropriate content (NSFW) with " . 
                                   round($nsfwScore * 100) . "% confidence.";
                } else {
                    $explanation = "NO. The image appears to be appropriate.";
                }
                
                return [
                    'is_inappropriate' => $isInappropriate,
                    'explanation' => $explanation,
                    'nsfw_score' => $nsfwScore,
                    'success' => true
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('NSFW image check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Use offline fallback if API check fails
            return $this->offlineImageCheck($imagePath);
        }
    }
    
    /**
     * Simple offline check for profile pictures based on image properties only
     * No content analysis, just basic format and size checks
     */
    private function offlineImageCheck(string $imagePath): array
    {
        try {
            // Get image size and dimensions
            list($width, $height, $type) = getimagesize($imagePath);
            $fileSize = filesize($imagePath) / 1024 / 1024; // Size in MB
            
            $this->logger->debug('Performing offline image check', [
                'width' => $width,
                'height' => $height,
                'size_mb' => $fileSize,
                'type' => $type
            ]);
            
            // Very large files might be suspicious
            if ($fileSize > 8) {
                return [
                    'is_inappropriate' => true,
                    'explanation' => 'The image is suspiciously large (over 8MB)',
                    'success' => true
                ];
            }
            
            // Check for unusually small images that might be trying to bypass detection
            if ($width < 100 || $height < 100) {
                return [
                    'is_inappropriate' => true,
                    'explanation' => 'The image is too small for a profile picture',
                    'success' => true
                ];
            }
            
            // Check aspect ratio for unusual values
            $aspectRatio = $width / $height;
            if ($aspectRatio > 3 || $aspectRatio < 0.33) {
                return [
                    'is_inappropriate' => true,
                    'explanation' => 'The image has an unusual aspect ratio',
                    'success' => true
                ];
            }
            
            return [
                'is_inappropriate' => false,
                'explanation' => 'The image passes basic format checks',
                'success' => true
            ];
        } catch (\Exception $e) {
            $this->logger->error('Offline image check failed', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'is_inappropriate' => false,
                'explanation' => 'Error during image analysis',
                'success' => false
            ];
        }
    }

    /**
     * Combined method that relies on model checks with retries for API failures
     */
    public function checkUserTextWithFallback(string $text): array
    {
        // Try the primary tagalog model
        try {
            $result = $this->checkUserText($text);
            if ($result['success']) {
                return $result;
            }
        } catch (\Exception $e) {
            $this->logger->error('Primary text check failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        // If primary fails, try just the hate speech model directly
        try {
            $categories = [];
            foreach (self::CONTENT_CATEGORIES as $category => $description) {
                $categories[$category] = [
                    'detected' => false,
                    'confidence' => 0,
                    'description' => $description
                ];
            }
            
            $hateResult = $this->makeApiRequest("https://api-inference.huggingface.co/models/{$this->hateSpeechModel}", [
                'inputs' => $text,
                'parameters' => [
                    'candidate_labels' => ["appropriate", "hate speech", "offensive", "extremist content"]
                ]
            ]);
            
            $this->processHateSpeechResults($hateResult, $categories);
            
            // Generate response
            $detectedCategories = [];
            $anyInappropriate = false;
            
            foreach ($categories as $category => $data) {
                if ($data['detected']) {
                    $detectedCategories[] = $data['description'] . ' (' . round($data['confidence'] * 100) . '% confidence)';
                    $anyInappropriate = true;
                }
            }
            
            if ($anyInappropriate) {
                $explanation = "The text contains inappropriate content: " . implode(', ', $detectedCategories);
            } else {
                $explanation = "The text appears to be appropriate";
            }
            
            return [
                'is_inappropriate' => $anyInappropriate,
                'explanation' => $explanation,
                'categories' => $categories,
                'success' => true
            ];
        } catch (\Exception $e) {
            $this->logger->error('Fallback text check also failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        // All models failed
        return [
            'is_inappropriate' => false, // Fail open for testing
            'explanation' => 'Text moderation systems unavailable',
            'success' => false
        ];
    }
    
    /**
     * Combined method for profile pictures using models only
     */
    public function checkProfilePicture(string $imagePath): array
    {
        try {
            // Try the full image check first
            $fullCheckResult = $this->checkImageContent($imagePath);
            
            if ($fullCheckResult['success']) {
                return $fullCheckResult;
            }
            
            // If full check fails, try the lightweight NSFW-only check
            $nsfwCheckResult = $this->checkImageWithNSFWModel($imagePath);
            
            if ($nsfwCheckResult['success']) {
                return $nsfwCheckResult;
            }
            
            // If all API checks fail, use offline fallback (format checks only)
            $this->logger->warning('All API image checks failed, using basic format checks');
            return $this->offlineImageCheck($imagePath);
            
        } catch (\Exception $e) {
            $this->logger->error('All image checks failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Last resort fallback
            return [
                'is_inappropriate' => false, // Fail open for testing
                'explanation' => 'Content moderation models unavailable',
                'success' => false
            ];
        }
    }
    
    /**
     * Process NSFW detection results
     */
    private function processNSFWResults($result, &$categories): void
    {
        if (empty($result) || !is_array($result)) {
            return;
        }
        
        $nsfwScore = 0;
        
        foreach ($result as $entry) {
            if (isset($entry['label']) && isset($entry['score'])) {
                if (strtolower($entry['label']) === 'nsfw' && $entry['score'] > $nsfwScore) {
                    $nsfwScore = $entry['score'];
                }
            }
        }
        
        if ($nsfwScore > 0.5) {
            $categories['sexual_content']['detected'] = true;
            $categories['sexual_content']['confidence'] = $nsfwScore;
            
            $this->logger->info('NSFW content detected', [
                'confidence' => $nsfwScore
            ]);
        }
    }
    
    /**
     * Process profanity detection results from Tagalog model
     */
    private function processProfanityResults($result, &$categories): void
    {
        if (empty($result) || !is_array($result)) {
            return;
        }
        
        // Log complete result for analysis
        $this->logger->debug('Tagalog profanity model result', [
            'result' => $result
        ]);
        
        // Most likely format for Tagalog profanity model
        if (isset($result[0][0]['label']) && isset($result[0][0]['score'])) {
            foreach ($result[0] as $entry) {
                $label = strtolower($entry['label']);
                $score = $entry['score'];
                
                // Log each label for analysis
                $this->logger->debug('Processing profanity label', [
                    'label' => $label,
                    'score' => $score
                ]);
                
                // Check NSFW label
                if ($label === 'nsfw' && $score > 0.7) {
                    $categories['offensive_gestures']['detected'] = true;
                    $categories['offensive_gestures']['confidence'] = max($categories['offensive_gestures']['confidence'], $score);
                    
                    $this->logger->info('Profanity/NSFW detected', [
                        'label' => $label,
                        'confidence' => $score
                    ]);
                }
            }
        }
        // Alternative format
        else if (isset($result[0]['label']) && isset($result[0]['score'])) {
            foreach ($result as $entry) {
                $label = strtolower($entry['label']);
                $score = $entry['score'];
                
                // Log each label for analysis
                $this->logger->debug('Processing profanity label', [
                    'label' => $label,
                    'score' => $score
                ]);
                
                // Check for abusive/profane labels
                if ((strpos($label, 'abusive') !== false || 
                     strpos($label, 'profan') !== false ||
                     strpos($label, 'hate') !== false ||
                     strpos($label, 'insult') !== false ||
                     strpos($label, 'toxic') !== false ||
                     $label === 'nsfw') && 
                    $score > 0.7) {
                    
                    $categories['offensive_gestures']['detected'] = true;
                    $categories['offensive_gestures']['confidence'] = max($categories['offensive_gestures']['confidence'], $score);
                    
                    $this->logger->info('Profanity detected', [
                        'label' => $label,
                        'confidence' => $score
                    ]);
                }
                
                // Check for violence-related labels
                if ((strpos($label, 'threat') !== false || 
                     strpos($label, 'violen') !== false) && 
                    $score > 0.7) {
                    
                    $categories['violence']['detected'] = true;
                    $categories['violence']['confidence'] = max($categories['violence']['confidence'], $score);
                    
                    $this->logger->info('Violence detected', [
                        'label' => $label,
                        'confidence' => $score
                    ]);
                }
            }
        }
    }
    
    /**
     * Process hate speech detection results
     */
    private function processHateSpeechResults($result, &$categories): void
    {
        if (empty($result) || !is_array($result)) {
            return;
        }
        
        // Log complete result for analysis
        $this->logger->debug('Hate speech model result', [
            'result' => $result
        ]);
        
        if (isset($result['labels']) && isset($result['scores']) && count($result['labels']) === count($result['scores'])) {
            for ($i = 0; $i < count($result['labels']); $i++) {
                $label = strtolower($result['labels'][$i]);
                $score = $result['scores'][$i];
                
                // Log each label for analysis
                $this->logger->debug('Processing hate speech label', [
                    'label' => $label,
                    'score' => $score
                ]);
                
                if (($label === 'hate speech' || $label === 'extremist content') && $score > 0.7) {
                    $categories['hate_symbols']['detected'] = true;
                    $categories['hate_symbols']['confidence'] = $score;
                    
                    $this->logger->info('Hate speech/symbols detected', [
                        'label' => $label,
                        'confidence' => $score
                    ]);
                }
                
                if ($label === 'offensive' && $score > 0.7) {
                    $categories['offensive_gestures']['detected'] = true;
                    $categories['offensive_gestures']['confidence'] = $score;
                    
                    $this->logger->info('Offensive content detected', [
                        'confidence' => $score
                    ]);
                }
            }
        }
    }
    
    /**
     * Extract caption from various response formats
     */
    private function extractCaption($result): string
    {
        if (empty($result)) {
            return '';
        }
        
        if (isset($result[0]['generated_text'])) {
            return $result[0]['generated_text'];
        }
        
        if (isset($result['generated_text'])) {
            return $result['generated_text'];
        }
        
        if (is_array($result) && isset($result[0]) && is_string($result[0])) {
            return $result[0];
        }
        
        return '';
    }
    
    /**
     * Make API request with retry logic and better error handling
     */
    private function makeApiRequest(string $endpoint, array $payload, int $timeout = 20, int $maxRetries = 2): array
    {
        $attempt = 0;
        $lastException = null;
        
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
                
                // Check HTTP status code
                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    $this->logger->warning('Non-200 response from API', [
                        'endpoint' => $endpoint,
                        'status_code' => $statusCode
                    ]);
                    
                    // If 503, wait longer before retry
                    if ($statusCode === 503) {
                        $attempt++;
                        if ($attempt >= $maxRetries) {
                            throw new \Exception("Service unavailable (503) after {$maxRetries} attempts");
                        }
                        sleep(pow(2, $attempt + 1)); // Longer wait for 503
                        continue;
                    }
                }
                
                // Get response content
                $responseContent = $response->getContent(false);
                
                // Try to decode JSON
                try {
                    return json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);
                } catch (\JsonException $e) {
                    // If not JSON, return as raw content
                    return ['raw_content' => $responseContent];
                }
            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                
                if ($attempt >= $maxRetries) {
                    throw $e;
                }
                
                sleep(pow(2, $attempt)); // Exponential backoff
            }
        }
        
        // We should never get here, but just in case
        throw new \Exception('API request failed after ' . $maxRetries . ' attempts: ' . 
                            ($lastException ? $lastException->getMessage() : 'Unknown error'));
    }
}