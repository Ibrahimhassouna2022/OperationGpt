# OperationGPT Prompt Documentation

This document explains how to customize the AI behavior in OperationGPT based on user roles.

## Architecture
The system uses a role-based prompt injection mechanism. When a user sends a message, the `OpenAIService` detects their role and fetches the corresponding prompt from `src/Config/prompts.php`.

## Roles Defined
The following roles are supported by default:

| Role | Target User | Default Behavior |
|------|-------------|------------------|
| `super_admin` | System Owners | High-level authority, full schema access. |
| `developer` | Tech Staff | CRUD operations, technical SQL generation. |
| `data_analyst`| Business Users| Reporting, aggregations, read-heavy operations. |
| `user` | End Users | Restricted to personal data context. |

## How to Customize Prompts

### 1. Modifying Existing Roles
Navigate to `src/Config/prompts.php`. Each role has a `prompt` key. You can change the 
text to alter the AI's "personality" or instructions.

Example of changing the `data_analyst` tone:
```php
'data_analyst' => [
    'prompt' => "You are a strictly read-only Data Analyst. Under no circumstances should you generate INSERT or DELETE queries.",
],
```

### 2. Adding New Roles
To add a new role (e.g., `manager`):
1. Add the role to the `roles` array in `prompts.php`.
2. Ensure your Laravel `User` model has a `role` attribute that matches this key.

```php
'manager' => [
    'name' => 'Department Manager',
    'prompt' => "You are a Managerial Assistant focusing on department-level stats.",
],
```

### 3. Global Rules
The `global_rules` array in `prompts.php` contains instructions that apply to **all** roles. This is where we enforce:
- JSON output format.
- Schema injection.
- Security constraints (like password hashing).
- Date formatting.

**Warning:** Do not remove the `:schema` or `:sql_query` keywords from global rules, as they are essential for the package to function.

## Placeholders
The system automatically replaces these placeholders in your prompts:
- `:schema`: The JSON representation of allowed tables.
- `:name`: The current user's name.
- `:identifier`: The current user's ID/Email.
- `:password_hash`: A default secure hash for new users.
- `:current_date`: The current server timestamp.

## منطق العمل البرمجي (Technical Logic)

بصفتك مهندس برمجيات تبني حزمة (Package)، تم تصميم النظام ليعطي أقصى مرونة للمطور الذي يستخدم حزمتك. إليك كيف يعمل الكود داخلياً:

### 1. استحضار البيانات والبرومبت المختص
يقوم الكود أولاً بجلب الجداول المسموح بها وتحديد دور المستخدم، ثم يسحب البرومبت الخاص بهذا الدور من الإعدادات:
```php
$rolePrompt = $roleConfig['prompt'];
$globalRules = implode("\n", config('operation-gpt-prompts.global_rules', []));
```

### 2. إستراتيجية الدمج أولاً (Merge First Strategy)
نقوم بدمج برومبت "الشخصية" (Role) مع برومبت "القوانين التقنية" (Global Rules) في نص واحد طويل قبل البدء بعملية الاستبدال:
```php
$systemPrompt = $rolePrompt . "\n\n" . $globalRules;
```
**لماذا هذه الخطوة مهمة جداً؟**
لأنك كمطور حزمة لا تعرف ماذا سيكتب المستخدم النهائي. بهذه الطريقة، نحن نسمح للمطور باستخدام العلامات المحجوزة (مثل `:name` أو `:schema`) في **أي مكان** يشاء، سواء في القواعد العامة أو حتى داخل وصف الدور.

**مثال على المرونة:**
يمكن للمطور تخصيص الدور في الإعدادات ليصبح:
`'prompt' => "أهلاً يا :name، أنت الآن المسؤول عن جداول :schema"`
وسيقوم الكود باستبدالها تلقائياً بالبيانات الحقيقية.

### 3. محرك الاستبدال الديناميكي (Dynamic Replacement Engine)
بدلاً من البحث والاستبدال في كل جزء على حدة، نقوم بتشغيل حلقة تكرارية واحدة على النص النهائي المدمج لتعويض كل العلامات بالقيم الحقيقية (اسم المستخدم، الوقت، هيكل الجداول، إلخ):
```php
foreach ($replacements as $key => $value) {
    $systemPrompt = str_replace($key, $value, $systemPrompt);
}
```
هذا يضمن كوداً أنظف (Clean Code)، أداءً أسرع، ومرونة مطلقة للمطور لتخصيص الحزمة دون الحاجة لتعديل الكود المصدري.

## شرح تفصيلي لمنطق الحماية (ChatController)

إليك شرح مفصل لما يحدث داخل دالة `executeQuerySecurely` لضمان أمان العمليات:

### 1. تحديد هوية المستخدم (Identity)
يبدأ الكود بجلب بيانات المستخدم المسجل حالياً (`Auth::user()`) لمعرفة معرفه الفريد (ID/Email) ودوره (Role). هذه الخطوة أساسية لتخصيص الاستعلام لاحقاً.

### 2. معالجة كلمات المرور (Password Hashing)
إذا احتوى الاستعلام على كلمة `password` وكان من نوع `UPDATE` أو `INSERT`:
*   يستخدم الكود **Regex** للبحث عن القيمة النصية لكلمة المرور.
*   يقوم بتشفيرها فوراً باستخدام `Hash::make` قبل وصولها لقاعدة البيانات.
*   هذا يمنع تخزين كلمات المرور كنصوص واضحة (Plain Text).

### 3. حقن الهوية الإجباري (Identity Injection)
للمستخدمين العاديين (غير المديرين)، يقوم الكود بفحص استعلامات التحديث (`UPDATE`):
*   يتحقق ما إذا كان هناك شرط `WHERE`.
*   يقوم بإجبار الاستعلام على التعديل في سجل المستخدم الحالي فقط عن طريق إضافة `WHERE user_id = '...'`.
*   هذا يمنع أي مستخدم من تعديل بيانات مستخدم آخر حتى لو حاول التلاعب بالبرومبت.

### 4. فحص الصلاحيات بناءً على الدور (Role-Based Permissions)
هذا هو قلب النظام المرن:
*   يتم جلب مصفوفة `allowed_operations` من ملف الإعدادات للدور الحالي.
*   يتم فحص أول كلمة في استعلام SQL (مثل `SELECT` أو `DELETE`).
*   إذا لم تكن الكلمة موجودة في القائمة المسموحة لهذا الدور، يتم رفض العملية فوراً مع رسالة توضيحية.

### 5. مرحلة التنفيذ (Execution)
يتم تقسيم التنفيذ لنوعين:
*   **استعلامات جلب البيانات (`SELECT`)**: يتم تنفيذها وإرسال النتائج لدالة تحولها لجدول HTML جميل للعرض.
*   **استعلامات التعديل (`DML`)**: يتم تنفيذها وحساب عدد الصفوف المتأثرة للتأكد من نجاح العملية.

### 6. معالجة الأخطاء (Error Handling)
في حال حدوث أي خطأ في قواعد البيانات (مثل خطأ في الصيغة)، يتم تسجيل الخطأ في الـ `Log` وإرسال رسالة خطأ آمنة للمستخدم لا تحتوي على تفاصيل حساسة عن الخادم.

