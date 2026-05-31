<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    |
    | Your OpenAI API key for interacting with GPT.
    |
    */
    'openai_api_key' => env('OPENAI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | OpenAI Model
    |--------------------------------------------------------------------------
    |
    | The model used for generating operations. GPT-4 is recommended.
    |
    */
    'model' => env('OPERATION_GPT_MODEL', 'gpt-4o'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Tables and Columns
    |--------------------------------------------------------------------------
    |
    | Security Layer: Only tables listed here can be accessed or modified.
    | For each table, list the columns that are allowed to be touched.
    |
    */
    'allowed_tables' => [
        'users' => ['id', 'name', 'email', 'password', 'role', 'created_at', 'updated_at'],
        // 'posts' => ['id', 'title', 'content', 'author_id'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Operation Logging
    |--------------------------------------------------------------------------
    |
    | Whether to log all operations performed by the package.
    |
    */
    'logging' => true,

];