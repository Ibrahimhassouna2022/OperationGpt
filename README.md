# OperationGpt 

A powerful Laravel package that allows users to interact with their system database using natural language via a beautiful chat interface.

## Features
- **AI-Driven DB Operations**: Insert, Update, and Select data using natural language.
- **Security-First Architecture**: AI never executes raw SQL. It returns structured instructions validated against a strict whitelist.
- **Natural Language Reports**: Automatically generates HTML tables/reports from DB data.
- **Automatic Hashing**: Passwords in `insert` actions are automatically hashed.
- **Production Ready**: Built with SOLID principles, transactions, and robust logging.

## Installation

1. **Install via Composer**:
   ```bash
   composer require operation-gpt/operation-gpt
   ```

2. **Publish Configuration**:
   This will create a `config/operation-gpt.php` file in your main application.
   ```bash
   php artisan vendor:publish --provider="OperationGpt\OperationGptServiceProvider"
   ```

3. **Configure OpenAI API Key**:
   Add your OpenAI credentials to your project's `.env` file:
   ```env
   OPENAI_API_KEY=sk-your-api-key
   OPERATION_GPT_MODEL=gpt-4o
   ```

4. **Define Security Scope (Allowed Tables)**:
   This is the most critical step. Open `config/operation-gpt.php` and define exactly which tables and columns the AI can interact with. 
   
   **Example:**
   ```php
   'allowed_tables' => [
       'employees' => ['id', 'name', 'position', 'salary'],
       'departments' => ['id', 'name'],
   ],
   ```
   *Note: If a table like `students` is not in this list, the AI will be completely blocked from accessing it, even if requested.*

## Usage

Access the professional chat interface at:
`your-app.test/operation-gpt`

### Example Commands:
- "أضف موظف جديد باسم محمد في قسم البرمجة براتب 5000"
- "تعديل راتب الموظف أحمد ليصبح 6000"
- "اعرض لي تقرير بجميع الموظفين في قسم المبيعات"

## 🛡 Security & Architecture

- **Strict Whitelist Control**: The package works on a "deny-by-default" basis. The AI only sees and touches what you explicitly define in the config. This protects sensitive tables (like `students` or `settings`) from accidental or malicious access.
- **Fail-Safe Parsing**: We use a custom `OperationParser` that validates every instruction from the AI. If the AI hallucinates or tries to return an unauthorized command, the system catches it and returns a clear error message in the UI.
- **No Raw SQL Execution**: The package translates natural language into structured JSON, which is then executed via Laravel's safe Query Builder.
- **Transaction Support**: All database actions are wrapped in transactions. If any part of a multi-step request fails, everything is rolled back automatically.

## 🧩 Core Architecture
The package has been simplified to leverage the full power of AI, moving from complex manual parsing to direct
AI-driven logic.
For developers looking to customize the internal logic:

OpenAIService.php: The "Brain & Architect".
This is where the core logic lives. It manages the interaction with the OpenAI API.
It contains the System Prompt which defines the AI's identity and its strict operational rules.
Customization: You can easily modify the AI's behavior by editing the RULES section in this file (e.g., adding specific business logic, restricting certain SQL commands, or changing the output language).
It's responsible for securely passing the allowed_tables schema to the AI so it knows exactly what it can work with.

ChatController.php: The "Execution Engine".
Acting as the bridge between the AI and your database.
Since the AI now generates direct, ready-to-use SQL within a JSON object, this controller takes the output and executes it directly using Laravel's DB facade.
This approach eliminates the need for separate Parser and Executor files, making the package lightweight and faster.



💡 Why this architecture?
Instead of writing long PHP classes to parse and map data, we now use the AI as a Live SQL Programmer. This makes the package extremely flexible; the AI can handle complex queries, joins, and filters dynamically based on your schema without any extra code.

## License
MIT
