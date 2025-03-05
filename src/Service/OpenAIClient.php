<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIClient
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function summarizeComments(array $comments): string
    {
        $prompt = "Summarize these comments in 2 to 3 sentences in a clear and impactful way.  
- Identify the main topic.  
- Capture general opinions (positive, negative, mixed).  
- Provide a smooth and readable summary, as if writing a professional article.  

Comments to analyze:\n\n" . implode("\n", $comments);
        $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an AI that summarizes user comments.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.5,
                'max_tokens' => 150,
            ],
        ]);

        $data = $response->toArray();
        return $data['choices'][0]['message']['content'] ?? 'No summary available.';
    }
}