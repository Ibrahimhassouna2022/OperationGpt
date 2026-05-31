# OperationGPT

AI-powered database assistant for Laravel applications.

OperationGPT allows authenticated users to interact with application data using natural language while enforcing strict security, role-based permissions, and database whitelisting.

---

## Features

* Natural language database operations
* Role-based permissions
* Table & column whitelisting
* OpenAI integration
* Secure password hashing
* Transaction-safe execution
* HTML report generation
* Customizable prompts per role
* Ready-to-use AI chat interface

---

## Requirements

* PHP 8.2+
* Laravel 11+
* OpenAI API Key

---

# Installation

Install the package using Composer:

```bash
composer require operation-gpt/operation-gpt
```

Publish the package assets and configuration files:

```bash
php artisan vendor:publish --provider="OperationGpt\OperationGptServiceProvider"
```

After publishing, the following configuration files will be available:

```text
config/
├── operation-gpt.php
└── operation-gpt-prompts.php
```

---

# Configure OpenAI

Open your application's `.env` file:

```env
OPENAI_API_KEY=your-api-key
OPERATION_GPT_MODEL=gpt-4o
```

### Purpose

* `OPENAI_API_KEY` authenticates requests to OpenAI.
* `OPERATION_GPT_MODEL` defines which model will be used.

---

# Displaying the Chat Interface

After installation, the package provides a ready-to-use Blade view:

```php
view('vendor.operation-gpt.chat')
```

You can display the AI chat interface using one of the following methods.

---

## Option 1: Create a Dedicated Route

Open:

```text
routes/web.php
```

Add:

```php
Route::get('/ai-chat', function () {
    return view('vendor.operation-gpt.chat');
})->name('ai.chat');
```

Now visit:

```text
http://your-domain.com/ai-chat
```

### Use this approach when:

* You want a standalone AI assistant page.
* You want a dedicated URL for the chatbot.

---

## Option 2: Include the Chat Inside an Existing Blade View

You can embed the AI assistant inside any Blade page.

```blade
@include('vendor.operation-gpt.chat')
```

### Use this approach when:

* You want the chatbot inside an existing dashboard.
* You want the assistant to appear beside other page content.

Examples:

* Teacher Dashboard
* Student Portal
* CRM Dashboard
* HR Management System

---

## Option 3: Return the View from Your Own Controller

```php
class TeacherController extends Controller
{
    public function aiAssistant()
    {
        return view('vendor.operation-gpt.chat');
    }
}
```

Route:

```php
Route::get('/teacher/assistant', [TeacherController::class, 'aiAssistant']);
```

### Use this approach when:

* You need middleware protection.
* You need authorization checks.
* You need custom layouts.
* You need to pass additional data to the page.

---

# Configure Allowed Tables

Open:

```text
config/operation-gpt.php
```

Example:

```php
'allowed_tables' => [

    'users' => [
        'id',
        'name',
        'email',
        'role',
    ],

    'students' => [
        'id',
        'name',
        'gpa',
    ],

],
```

### Purpose

OperationGPT follows a whitelist security model.

Only the tables and columns defined here can be accessed by the AI.

Any table not listed here is automatically blocked.

---

# Configure Roles & Permissions

Open:

```text
config/operation-gpt-prompts.php
```

Example:

```php
'roles' => [

    'teacher' => [

        'name' => 'Teacher',

        'constraints' => [

            'allowed_operations' => [
                'SELECT',
                'UPDATE'
            ],

            'enforce_self_only' => false,

        ],

    ],

],
```

### Purpose

Defines what each role can do.

Example above:

✅ SELECT

✅ UPDATE

❌ INSERT

❌ DELETE

---

# Configure AI Prompts

All role prompts are located in:

```text
config/operation-gpt-prompts.php
```

Example:

```php
'teacher' => [

    'prompt' => "
        You are a Teacher Assistant.
        You can read all student records.
        You can update student GPA.
        You cannot delete records.
    ",

],
```

### Purpose

Prompts define how the AI behaves for each role.

You can create different behaviors for:

* Administrators
* Teachers
* Students
* Employees
* Managers

---

# Configure Global Rules

Inside:

```text
config/operation-gpt-prompts.php
```

Locate:

```php
'global_rules' => [

    "OUTPUT: JSON ONLY",

    "SCHEMA: :schema",

    "USER CONTEXT: :identifier",

],
```

### Purpose

These rules are automatically appended to every role prompt.

Use them to enforce:

* Output format
* Security requirements
* Global AI instructions
* System-wide restrictions

---

# User Role Requirement

The package expects the authenticated user to have a role.

Example:

```php
Auth::user()->role;
```

Recommended migration:

```php
$table->string('role');
```

Example roles:

```text
super_admin
teacher
student
user
```

If no role exists, the package automatically falls back to:

```text
user
```

---

# Example Usage

User:

```text
Show all students with GPA greater than 90
```

User:

```text
Update Ahmed's GPA to 95
```

User:

```text
Create a new teacher named John
```

The AI converts the request into a structured operation, validates permissions, and executes the action safely.

---

# Security

OperationGPT applies multiple layers of protection.

## Table Whitelisting

Only configured tables can be accessed.

---

## Operation Validation

Each role can execute only its allowed operations.

---

## Password Hashing

Passwords are automatically hashed using Laravel's Hash::make() before being stored.

---

## Self Scope Enforcement

Roles configured with:

```php
'enforce_self_only' => true
```

can only access or modify their own records.

---

## Transactions

All write operations are executed inside database transactions.

Any failure automatically triggers a rollback.

---

# Customization Guide

## Modify Allowed Tables

File:

```text
config/operation-gpt.php
```

Purpose:

Control which database tables and columns the AI can access.

---

## Modify Roles & Permissions

File:

```text
config/operation-gpt-prompts.php
```

Purpose:

Define role restrictions and allowed operations.

---

## Modify Role Prompts

File:

config/operation-gpt-prompts.php

Purpose:

Customize AI behavior for each role.

---

## Modify Global Rules

File:

```text
config/operation-gpt-prompts.php
```

Purpose:

Apply instructions to every AI request.

---

## Modify OpenAI Request Logic

File:

```text
src/Services/OpenAIService.php
```

Purpose:

Customize model requests, placeholders, and prompt generation.

---

## Modify Query Execution Logic

File:

```text
src/Http/Controllers/ChatController.php
```

Purpose:

Customize validation, query execution, security checks, and report generation.

---

## Modify Chat Interface

File:

```text
resources/views/chat.blade.php
```

Purpose:

Customize the AI chat UI.

---

# Workflow

```text
User Request
      ↓
Role Detection
      ↓
Prompt Compilation
      ↓
OpenAI Response
      ↓
Permission Validation
      ↓
Database Transaction
      ↓
Result Formatting
      ↓
Response To User
```

---

# License

MIT License

Created with ❤️ for secure AI-powered database interactions in Laravel.
