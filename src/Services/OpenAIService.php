<?php

namespace OperationGpt\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        $user = Auth::user();
        if (!$user) {
            return json_encode(['error' => 'User not authenticated.']);
        }

        $userIdentifierColumn = $user->getAuthIdentifierName();
        $userIdentifier = $user->getAuthIdentifier();
        $primaryKey = $user->getKeyName();
        $userRole = $user->role ?? 'user';
        $userName = $user->name ?? 'User';

        $allowedTables = config('operation-gpt.allowed_tables', []);
        $schemaInfo = json_encode($allowedTables);
      
        // Fetch role-based prompt from configuration
        $roleConfigs = config('operation-gpt-prompts.roles', []);
        $roleConfig = $roleConfigs[$userRole] ?? ($roleConfigs['user'] ?? [
            'prompt' => "You are a standard Database Assistant. Role: User. Capability: You can ONLY SELECT data from the allowed tables.",
            'constraints' => ['allowed_operations' => ['SELECT']]
        ]);
        
        $rolePrompt = $roleConfig['prompt'] ?? "You are a standard Database Assistant. Role: User. Capability: You can ONLY SELECT data from the allowed tables.";
        $globalRules = implode("\n", config('operation-gpt-prompts.global_rules', []));

        // Prepare variables for replacement
        $replacements = [
            ':schema' => $schemaInfo,
            ':name' => $userName,
            ':identifier' => $userIdentifier,
            ':password_hash' => Hash::make('123456789'),
            ':current_date' => now()->format('Y-m-d H:i:s'),
        ];

        // Construct the final system prompt by merging role-specific instructions with global package rules.
        // This merge allows developers to use placeholders (like :name) in both the role prompt and global rules.
        $systemPrompt = $rolePrompt . "\n\n" . $globalRules;

        // Dynamic Replacement Engine:
        // This loop iterates through the 'replacements' dictionary and swaps placeholders with real-time data.
        // It ensures the AI receives a fully contextualized instruction set with actual user/schema info.
        foreach ($replacements as $key => $value) {
            $systemPrompt = str_replace($key, $value, $systemPrompt);
        }


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

            $body = $response->getBody()->getContents();
            $decoded = json_decode($body, true);

            if (config('operation-gpt.logging', true)) {
                Log::info("OperationGPT AI RAW RESPONSE: " . $body);
            }

            if (isset($decoded['choices'][0]['message']['content'])) {
                return $decoded['choices'][0]['message']['content'];
            }

            return json_encode(['sql_query' => null, 'error' => 'AI did not generate a query.']);

        } catch (\Exception $e) {
            Log::error('OperationGPT OpenAIService Error: ' . $e->getMessage());
            return json_encode(['error' => $e->getMessage()]);
        }
    }
}