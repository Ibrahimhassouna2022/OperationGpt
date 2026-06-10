<?php

/**
 * OperationGPT Prompt Configuration
 * 
 * This file contains the system prompts used for different user roles.
 * Developers can customize these prompts to change how the AI behaves 
 * for specific types of users.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Role Prompts
    |--------------------------------------------------------------------------
    |
    | These prompts define the "personality" and constraints for each role.
    | 
    | HOW TO CUSTOMIZE:
    | 1. Find the role you want to modify.
    | 2. Edit the 'prompt' string.
    | 3. You can use placeholders like :schema, :name, :identifier, etc., 
    |    which are replaced dynamically in OpenAIService.php.
    |    Example: 'prompt' => "Hello :name, you are managing :schema tables."
    |
    */

    'roles' => [

        /*
        |--------------------------------------------------------------------------
        | Example Role (Template)
        |--------------------------------------------------------------------------
        |
        | You can define as many roles as you need by following this exact structure.
        | Just copy this 'example_role' block, rename the key (e.g., 'manager', 'editor'),
        | and adjust the settings below.
        |
        */
        'example_role' => [
            // 1. Role Display Name
            'name' => 'Example Admin',
            
            // 2. Role Description (For your own reference or UI display)
            'description' => 'This is an example role that demonstrates all available configuration options.',
            
            // 3. Security Constraints
            'constraints' => [
                // Which SQL operations are allowed? (e.g., SELECT, INSERT, UPDATE, DELETE)
                'allowed_operations' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
                
                // Which tables can this role access? (Leave empty or don't set to allow all, if handled by prompt)
                'allowed_tables' => ['users', 'posts', 'comments'],
                
                // Set to true if the user should ONLY be able to see/modify their own data (enforced via user ID)
                'enforce_self_only' => false,
                
                // Optional: Force specific WHERE conditions for certain tables.
                // This defines EXACTLY ON WHOM the 'allowed_operations' (defined above) can be applied.
                // For example: Restrict this role to only apply operations on their own record in the 'users' table.
                'allowed_query_conditions' => [
                    'users' => [
                        "role = 'user'",     // Mandatory condition: Operations apply ONLY to users with the 'user' role
                        "id = :identifier"   // Mandatory condition: Operations apply ONLY to the current user's own record
                    ]
                ] 
            ],
            
            // 4. System Prompt (AI Instructions)
            'prompt' => "You are a Database Assistant.
                Role: Example Admin.
                Capability: You have access to users, posts, and comments.
                Constraints: Generate raw clean SQL based on the user's request.",
        ],

        // ------------------------------------------------------------------------
        // Add more roles below following the same pattern as 'example_role'
        // ------------------------------------------------------------------------

    ],

    /*
    |--------------------------------------------------------------------------
    | Global Prompt Rules
    |--------------------------------------------------------------------------
    |
    | These rules are appended to every role-based prompt to ensure
    | consistent output format and security.
    |
    */
    'global_rules' => [
        "OUTPUT: JSON ONLY. Key: 'sql_query'.",
        "SCHEMA: :schema",
        "USER CONTEXT: Name: ':name', ID: ':identifier'.",
        "SECURITY: Use :password_hash for new passwords.",
        "DATE: Use ':current_date' for timestamps.",
        "FORMAT: NO MARKDOWN. NO ```json blocks. Just the raw JSON string.",
    ],

];
