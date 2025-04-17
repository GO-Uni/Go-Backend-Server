<?php

namespace App\Services;

use GuzzleHttp\Client;

class OpenAIService
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('OPENAI_API_KEY');
    }

    public function getRecommendations($activities)
    {
        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Analyze the following user activities and recommend the most liked categories based on their interactions. 
                                      Focus on categories with the highest engagement (saves, high ratings, or positive reviews). 
                                      Format the response as category names under each other only." . json_encode($activities)
                    ]
                ],
                'max_tokens' => 100,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['choices'][0]['message']['content'] ?? null;
    }

    /**
     * Generate a chatbot response based on user query and destinations
     */
    public function generateChatbotResponse($userQuery, $destinations)
    {
        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a travel assistant chatbot that helps users find 
                                        specific destinations (by name, category, or district)
                                        or get personalized recommendations based on their preferences.
                                        Only respond to destination-related requests—politely guide users 
                                        back to travel topics if they ask unrelated questions."
                    ],
                    [
                        'role' => 'user',
                        'content' => $userQuery
                    ]
                ],
                'max_tokens' => 150,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['choices'][0]['message']['content'] ?? "I'm sorry, I couldn't process your request.";
    }

    public function nameDetector($userQuery)
    {
        $response = $this->client->post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => "You are a travel assistant that extracts and normalizes the following from user queries:
                    1. Destination names (businesses, landmarks)
                    2. Categories (normalized to singular form, e.g., 'hotels' → 'hotel')
                    3. Districts/locations
                    
                    Rules:
                    - Always return only the most relevant term
                    - Normalize categories to singular form
                    - For ambiguous terms, choose the most likely travel-related option
                    - If nothing is found, respond exactly with 'None'
                    - Never add explanations or notes"
                    ],
                    [
                        'role' => 'user',
                        'content' => $userQuery
                    ]
                ],
                'temperature' => 0.3,  
                'max_tokens' => 30,
                'top_p' => 0.9,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        $result = trim($data['choices'][0]['message']['content'] ?? "None");

        return $result === "" ? "None" : $result;
    }
}
