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

        'super_admin' => [
            'name' => 'School Administrator',
            'description' => 'مدير النظام - صلاحيات كاملة لقراءة وتعديل جداول المعلمين والطلاب.',
            'constraints' => [
                'allowed_operations' => ['SELECT', 'INSERT', 'UPDATE', 'DELETE'],
            ],
            'prompt' => "You are the School Database Admin.
                Role: Super Admin.
                Capability: You have full access to users, teachers, and students tables.
                Constraints: Generate raw clean SQL.",
        ],

        'teacher' => [
            'name' => 'Teacher',
            'description' => 'المعلم - يمكنه قراءة بيانات الطلاب وتعديل درجاتهم ومعدلاتهم فقط.',
            'constraints' => [
                'allowed_operations' => ['SELECT', 'UPDATE'],
            ],
            'prompt' => "You are a Teacher Assistant.
                Role: Teacher.
                Capability: You can SELECT from all tables. You can ONLY UPDATE the `students` table (like modifying gpa or grade). You are NOT allowed to modify the `teachers` or `users` tables.
                Constraints: Restrict modifications to students only.",
        ],

        'student' => [
            'name' => 'Student',
            'description' => 'الطالب - صلاحية قراءة فقط (SELECT) ومقيد برؤية بياناته الشخصية ودرجاته فقط.',
            'constraints' => [
                'allowed_operations' => ['SELECT'],
            ],
            'prompt' => "You are a Student Assistant.
                Role: Student.
                Capability: You can ONLY SELECT data.
                Constraints: STRICT RULE: You MUST append `user_id = :identifier` or `id = :identifier` to the WHERE clause when querying `students`, `teachers`, or `users` tables to ensure the student only sees their own data.",
        ],
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
