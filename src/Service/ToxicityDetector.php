<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ToxicityDetector
{
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $apiKey)
    {
        $this->client = $client;
        $this->apiKey = $apiKey;
    }

    public function analyzeToxicity(string $text): array
    {
        $response = $this->client->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a content moderator. Analyze the following text for toxic content, profanity, or inappropriate language. Respond with a JSON object containing: isToxic (boolean), toxicWords (array of toxic words found), and reason (explanation).'
                    ],
                    [
                        'role' => 'user',
                        'content' => $text
                    ],
                ],
                'temperature' => 0.3,
            ],
        ]);

        $data = $response->toArray();
        $analysis = json_decode($data['choices'][0]['message']['content'], true);

        return $analysis ?? [
            'isToxic' => false,
            'toxicWords' => [],
            'reason' => 'Analysis failed'
        ];
    }
} 