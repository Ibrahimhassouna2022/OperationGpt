<?php

namespace OperationGpt\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->apiKey = config('operation-gpt.openai_api_key');
        $this->model = config('operation-gpt.model', 'gpt-4o');

        $this->client = new Client([
            'base_uri' => 'https://api.openai.com/v1/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 30,
        ]);
    }

    public function sendMessage(string $message)
    {
        $allowedTables = config('operation-gpt.allowed_tables');
        $schemaInfo = json_encode($allowedTables);

        $systemPrompt = "You are a professional database assistant. 
        Your task is to convert natural language requests into a single, valid SQL query.
        
        ALLOWED TABLES AND SCHEMA: {$schemaInfo}
        
        RULES:
        1. ONLY return a JSON object.
        2. The JSON must contain a key named 'SQL query' which holds the SQL statement.
        3. ALWAYS use the allowed tables and columns provided above.
        4. NEVER explain anything or provide any text outside the JSON.
        5. If the request cannot be fulfilled, return: { \"error\": \"Reason why it's not possible\" }";


        try {
            $response = $this->client->post('chat/completions', [
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            return $data['choices'][0]['message']['content'] ?? '';
            
        } catch (\Exception $e) {
            Log::error('OperationGpt OpenAI Error: ' . $e->getMessage());
            return json_encode([
                'type' => 'error',
                'message' => 'Failed to connect to AI service: ' . $e->getMessage()
            ]);
        }
    }
}