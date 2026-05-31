<?php

/**
 * OperationGPT - Comprehensive Roles & Constraints Test
 * يختبر هذا السكربت جميع الأدوار، ويختبر ماذا يحدث إذا خالف المستخدم القيود 
 * (نفترض هنا أن الذكاء الاصطناعي تم خداعه ووافق على توليد الاستعلام الخبيث).
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Illuminate\Http\Request;

$app = require_once __DIR__ . '/bootstrap/app.php';
$symfonyRequest = SymfonyRequest::create('/operation-gpt/chat', 'POST');
$laravelRequest = Request::createFromBase($symfonyRequest);
$app->instance('request', $laravelRequest);
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║          OperationGPT Package - Full Security Audit          ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

function executeTest(string $description, string $sql, string $expectedResult, string $notes = '') {
    $user = Auth::guard('web')->user();
    $userIdentifierColumn = $user->getAuthIdentifierName();
    $userIdentifier = $user->getAuthIdentifier();
    $userRole = $user->role ?? 'user';

    $roleConfigs = config('operation-gpt-prompts.roles', []);
    $roleConfig  = $roleConfigs[$userRole] ?? [];
    $allowedOps  = $roleConfig['constraints']['allowed_operations'] ?? ['SELECT'];

    $sqlUpper = strtoupper(trim($sql));
    $operation = explode(' ', $sqlUpper)[0];

    $isAllowed = false;
    foreach ($allowedOps as $op) {
        if (str_starts_with($sqlUpper, strtoupper($op))) {
            $isAllowed = true;
            break;
        }
    }

    echo "┌─────────────────────────────────────────────────────────────┐\n";
    echo "│ السيناريو: {$description}\n";
    echo "│ الاستعلام المولد: {$sql}\n";
    
    if (!$isAllowed) {
        echo "│ ❌ فشل بالمرور (ممنوع من Controller)\n";
        echo "│ 🛡️ حماية النظام: نجحت في منعه لأن عملية {$operation} ممنوعة لهذا الدور.\n";
    } else {
        echo "│ ✅ مسموح بالمرور (العملية {$operation} مسموحة)\n";
        
        $isAdmin = in_array($userRole, ['admin', 'super_admin']);
        $finalSql = $sql;
        if (!$isAdmin && str_contains($sqlUpper, 'UPDATE')) {
            if (stripos($sql, 'WHERE') !== false) {
                $finalSql = preg_replace('/WHERE\b.*/i', "WHERE {$userIdentifierColumn} = '{$userIdentifier}'", $sql);
            } else {
                $finalSql = rtrim(trim($sql), ';') . " WHERE {$userIdentifierColumn} = '{$userIdentifier}'";
            }
            echo "│ ⚠️ تدخل النظام الإجباري (Identity Injection):\n";
            echo "│ الاستعلام النهائي: {$finalSql}\n";
        }

        try {
            if (stripos(trim($finalSql), 'SELECT') === 0) {
                $rows = DB::select($finalSql);
                echo "│ 📊 نتيجة التنفيذ: نجاح (تم إرجاع " . count($rows) . " سجلات)\n";
            } else {
                $affected = DB::affectingStatement($finalSql);
                echo "│ ⚙️ نتيجة التنفيذ: نجاح (تم تعديل {$affected} سجل)\n";
            }
        } catch (\Exception $e) {
            echo "│ 💥 خطأ قاعدة بيانات: " . $e->getMessage() . "\n";
        }
    }

    if ($notes) {
        echo "│ 💡 تحليل النتيجة: {$notes}\n";
    }
    echo "└─────────────────────────────────────────────────────────────┘\n\n";
}

// ------------------------------------------------------------------
// 1. اختبار دور: الطالب (Student)
// ------------------------------------------------------------------
$student = \App\Models\User::where('email', 'student@school.com')->first();
Auth::guard('web')->setUser($student);
echo "▶️ تم الدخول بحساب الطالب: {$student->name} (ID: {$student->id})\n";
echo "   الصلاحيات المسموحة: [SELECT]\n\n";

executeTest(
    "الطالب يلتزم بالقيود - يستعلم عن درجاته الخاصة",
    "SELECT * FROM students WHERE user_id = 4",
    "نجاح",
    "تصرف سليم والنتيجة صحيحة."
);

executeTest(
    "الطالب يخالف القيود - يحاول الاستعلام عن درجات طالب آخر (Prompt Bypass)",
    "SELECT * FROM students WHERE user_id = 1",
    "نجاح",
    "⚠️ خطير: النظام (Controller) لا يتحقق من الـ user_id في الـ SELECT. يعتمد فقط على الذكاء الاصطناعي لمنعه، وإذا فشل الذكاء الاصطناعي سيتم كشف بيانات الطلاب الآخرين!"
);

executeTest(
    "الطالب يخالف القيود - يحاول تعديل درجته (UPDATE)",
    "UPDATE students SET gpa = 4.0 WHERE user_id = 4",
    "ممنوع",
    "ممتاز: الكنترولر منعه فوراً لأن UPDATE ليست من صلاحياته."
);

// ------------------------------------------------------------------
// 2. اختبار دور: المعلم (Teacher)
// ------------------------------------------------------------------
$teacher = \App\Models\User::where('email', 'teacher@school.com')->first();
Auth::guard('web')->setUser($teacher);
echo "\n▶️ تم الدخول بحساب المعلم: {$teacher->name} (ID: {$teacher->id})\n";
echo "   الصلاحيات المسموحة: [SELECT, UPDATE]\n\n";

executeTest(
    "المعلم يلتزم بالقيود - يحاول تعديل درجة الطالب خالد",
    "UPDATE students SET gpa = 3.9 WHERE user_id = 4",
    "خطأ منطقي",
    "⚠️ خلل (Bug): النظام أجبر الاستعلام ليصبح WHERE id=3 (رقم المعلم) مما أدى لفشل تعديل درجة الطالب. المعلم لا يستطيع تعديل الدرجات بسبب هذا الحقن."
);

executeTest(
    "المعلم يخالف القيود - يحاول تعديل راتبه (Prompt Bypass)",
    "UPDATE teachers SET salary = 9000 WHERE user_id = 3",
    "تم التعديل",
    "⚠️ خطير جداً: المعلم نجح في تعديل راتبه! لأن الكنترولر يجبر الـ UPDATE على ID المعلم (وهذا السجل خاص به) والعملية UPDATE مسموحة له."
);

executeTest(
    "المعلم يخالف القيود - يحاول ترقية نفسه لـ Admin (Prompt Bypass)",
    "UPDATE users SET role = 'super_admin' WHERE id = 3",
    "تم التعديل",
    "🚨 ثغرة أمنية حرجة: المعلم قام بترقية حسابه إلى Admin! النظام سمح بعملية الـ UPDATE واعتبر الاستعلام آمناً لأن الـ ID يطابق المعلم."
);

// ------------------------------------------------------------------
// 3. اختبار دور: المدير (Super Admin)
// ------------------------------------------------------------------
$admin = \App\Models\User::where('email', 'admin@school.com')->first();
Auth::guard('web')->setUser($admin);
echo "\n▶️ تم الدخول بحساب المدير: {$admin->name} (ID: {$admin->id})\n";
echo "   الصلاحيات المسموحة: [SELECT, INSERT, UPDATE, DELETE]\n\n";

executeTest(
    "المدير يحاول تعديل راتب المعلم",
    "UPDATE teachers SET salary = 5000 WHERE user_id = 3",
    "نجاح",
    "صحيح: المدير يستثنى من حقن الـ ID، لذلك الاستعلام يمر كما هو."
);

executeTest(
    "المدير يحاول حذف جدول (غير مسموحة صراحة في القائمة)",
    "DROP TABLE students",
    "ممنوع",
    "ممتاز: عملية DROP غير موجودة في مصفوفة المدير، وتم منعه بنجاح."
);
