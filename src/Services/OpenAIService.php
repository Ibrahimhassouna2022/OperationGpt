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
    $user = \Illuminate\Support\Facades\Auth::user();
    $userIdentifier = $user->employee_number; 
    $userRole = $user->role;

    $allowedTables = config('operation-gpt.allowed_tables');
    $schemaInfo = json_encode($allowedTables);

    // تحسين التعليمات لتكون قصيرة وحاسمة
    if ($userRole !== 'admin') {
        $roleInstruction = "USER: Employee (No: {$userIdentifier}). ONLY UPDATE own email/password. WHERE employee_number = '{$userIdentifier}' IS MANDATORY.";
    } else {
        $roleInstruction = "USER: Admin. Full access. Table 'employees' PrimaryKey is 'employee_number'. NO 'id' COLUMN EXISTS.";
    }

    // $systemPrompt = "You are a Laravel SQL Generator. 
    //     JSON ONLY. Key: 'sql_query'.
    //     SCHEMA: {$schemaInfo}
    //     CONTEXT: {$roleInstruction}
        
    //     CRITICAL RULES:
    //     1. COLUMN 'id' DOES NOT EXIST. Use 'employee_number'.
    //     2. FOR INSERT: You MUST include (employee_number, name, email, password, position, salary, role, created_at, updated_at).
    //     3. DATE: Use '" . now()->format('Y-m-d H:i:s') . "' for created_at/updated_at.
    //     4. PASSWORD: Use '" . \Illuminate\Support\Facades\Hash::make('123456789') . "' for new users.
    //     5. SEARCH: Use TRIM(name) LIKE '%name%'.
    //     6. NO MARKDOWN. NO EXPLANATION. ONLY JSON.";
    $user = auth()->user();
$systemPrompt = "You are a Laravel SQL Generator. 
    OUTPUT: JSON ONLY. Key: 'sql_query'.
    SCHEMA: {$schemaInfo}
    
    USER CONTEXT:
    - Current Authenticated User: Name: '{$user->name}', Number: '{$user->employee_number}'.
    - Use employee_number = '{$user->employee_number}' ONLY when the user refers to themselves (e.g., 'my salary', 'me').

    CRITICAL RULES FOR INSERT (New Employee):
    1. UNIQUE ID: ALWAYS generate a NEW unique 'employee_number'. Format: 'EMP-' + Current Year + '-' + 4 random digits (e.g., EMP-2026-9999). NEVER use the Current User's ID.
    2. NO NULLS: Never send NULL for 'role', 'position', or 'salary'.
    3. DEFAULT VALUES: If the user doesn't specify details for a new employee, use:
       - role: 'employee'
       - position: 'Staff'
       - salary: 3000
    4. DATA INTEGRITY: Ensure 'email' is unique and formatted correctly (e.g., name@example.com).
    5. SECURITY: Use this password hash for new users: '" . \Illuminate\Support\Facades\Hash::make('123456789') . "'.
    6. UNIQUE EMAIL: For new inserts, if the user doesn't provide an email, generate a unique one using the random number (e.g., user1234@example.com) to avoid 'Duplicate entry' errors.
    GENERAL RULES:
    - NO 'id' column. Primary key is 'employee_number'.
    - CURRENT DATE: Use '" . now()->format('Y-m-d H:i:s') . "'.
    - Use LIKE '%name%' for flexible name searching.
    - NO MARKDOWN (Do not use ```json).";

    try {
        $response = $this->client->post('chat/completions', [
            'json' => [
                // نصيحة: إذا كنت تستخدم الحساب المجاني، تأكد من استخدام gpt-3.5-turbo-0125 أو gpt-4o-mini لدعم أفضل للـ JSON
                'model' => 'gpt-3.5-turbo-0125', 
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $message],
                ],
                'response_format' => ['type' => 'json_object']
            ]
        ]);

        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);

        // إضافة Log هنا لمساعدتنا في رؤية ماذا يرسل الـ AI بالضبط في الـ Storage/logs/laravel.log
        \Illuminate\Support\Facades\Log::info("AI RAW RESPONSE: " . $body);

        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }

        return json_encode(['sql_query' => null, 'error' => 'الذكاء الاصطناعي لم يولد استعلاماً.']);

    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('OpenAIService Error: ' . $e->getMessage());
        return json_encode(['error' => $e->getMessage()]);
    }
}
}