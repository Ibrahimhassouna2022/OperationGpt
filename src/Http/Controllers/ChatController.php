<?php

namespace OperationGpt\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use OperationGpt\Services\OpenAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChatController extends Controller
{
    /**
     * Show the chat interface.
     */
    public function index()
    {
        return view('operation-gpt::chat');
    }

    /**
     * Handle the chat request.
     */
    public function chat(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'type' => 'error', 
                'reply' => 'يرجى تسجيل الدخول أولاً.',
                'message' => 'User not authenticated.'
            ], 401);
        }

        try {
            $request->validate(['message' => 'required|string|max:1000']);
            $message = $request->input('message');

            $openAIService = new OpenAIService();
            $aiResponse = $openAIService->sendMessage($message);

            $cleanResponse = preg_replace('/```json|```/', '', $aiResponse);
            $data = json_decode(trim($cleanResponse), true);

            $sql = $data['sql_query'] ?? null;

            if (!$sql) {
                Log::error("Failed AI Response: " . $aiResponse);
                return response()->json([
                    'type' => 'error', 
                    'reply' => 'لم يتم تكوين استعلام. يرجى إعادة صياغة طلبك بطريقة أوضح.',
                    'message' => 'لم يتم تكوين استعلام.'
                ], 422);
            }

            return $this->executeQuerySecurely($sql);

        } catch (\Exception $e) {
            Log::error("ChatController Error: " . $e->getMessage());
            return response()->json([
                'type' => 'error',
                'reply' => 'حدث خطأ في النظام. يرجى المحاولة لاحقاً.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function executeQuerySecurely($sql)
    {
        // جلب بيانات مستخدم المصادقة والمعرفات
        $user = Auth::user(); 
        $userIdentifierColumn = $user->getAuthIdentifierName();
        $userIdentifier = $user->getAuthIdentifier(); 
        $userRole = $user->role ?? 'user';

        // جلب مصفوفة الإعدادات الخاصة بدور المستخدم الحالي
        $roleConfigs = config('operation-gpt-prompts.roles', []);
        $roleConfig = $roleConfigs[$userRole] ?? ($roleConfigs['user'] ?? []);
        $roleConstraints = $roleConfig['constraints'] ?? [];

        // =========================================================================
        // 🛡️ طبقة الأمان الأولى: فحص القائمة البيضاء للجداول (Allowed Tables)
        // =========================================================================
        $allowedTables = $roleConstraints['allowed_tables'] ?? []; 
        
        // استخراج أسماء الجداول المطلوبة من الاستعلام باستخدام نمط Regex العام
        $tablePattern = '/(?:FROM|JOIN|UPDATE|INTO)\s+[`"\'\s]*([a-zA-Z0-9_]+)/i';
        preg_match_all($tablePattern, $sql, $matches);
        $requestedTables = array_unique(array_map('strtolower', $matches[1] ?? []));

        // حظر الاستعلام فوراً إذا طلب جدول غير مدرج في القائمة البيضاء لهذا الدور
        foreach ($requestedTables as $table) {
            if (!in_array($table, array_map('strtolower', $allowedTables))) {
                return response()->json([
                    'type' => 'error',
                    'reply' => 'عذراً، لا تمتلك الصلاحية للوصول إلى أحد الجداول المطلوبة في هذا الاستعلام.',
                    'message' => "Access to unlisted table ($table) denied for role: $userRole."
                ], 403);
            }
        }

        // --- 1. Password Hash Processing ---
        if (stripos($sql, 'password') !== false && (stripos($sql, 'UPDATE') !== false || stripos($sql, 'INSERT') !== false)) {
            $pattern = "/password\s*=\s*['\"]([^'\"]+)['\"]/i";
            if (preg_match($pattern, $sql, $matches)) {
                $plainPassword = $matches[1];
                $hashedPassword = Hash::make($plainPassword);
                $sql = str_replace("'$plainPassword'", "'$hashedPassword'", $sql);
                $sql = str_replace("\"$plainPassword\"", "'$hashedPassword'", $sql);
            }
        }

        $sqlUpper = strtoupper(trim($sql));

        // =========================================================================
        // 🛡️ طبقة الأمان الثانية: التحقق الديناميكي العام من الشروط (لمنع تعديل مستخدمين آخرين)
        // =========================================================================
        $allowedQueryConditions = $roleConstraints['allowed_query_conditions'] ?? [];

        if (!empty($allowedQueryConditions) && (str_contains($sqlUpper, 'UPDATE') || str_contains($sqlUpper, 'DELETE'))) {
            foreach ($allowedQueryConditions as $targetTable => $conditions) {
                if (stripos($sql, $targetTable) !== false) {
                    $hasValidCondition = false;

                    // تنظيف نص الاستعلام الحالي تماماً من المسافات والتنصيص للمقارنة العادلة
                    $cleanSql = str_replace([' ', "'", '"', '`'], '', $sql);

                    foreach ($conditions as $condition) {
                        // استبدال رمز النائب :identifier بالمعرف الحقيقي الحالي للمستخدم
                        $parsedCondition = str_replace(':identifier', $userIdentifier, $condition);
                        
                        // تنظيف الشرط المتوقع من المسافات وعلامات التنصيص أيضاً
                        $cleanCondition = str_replace([' ', "'", '"', '`'], '', $parsedCondition);

                        // إذا وجدنا الشرط الآمن متوفراً داخل نص الاستعلام
                        if (stripos($cleanSql, $cleanCondition) !== false) {
                            $hasValidCondition = true;
                            break; 
                        }
                    }

                    // حظر الاختراق فوراً إذا لم يستوفِ الاستعلام أي شرط مسموح في الـ Config للجدول المستهدف
                    if (!$hasValidCondition) {
                        return response()->json([
                            'type' => 'error',
                            'reply' => 'عذراً، الاستعلام لا يستوفي شروط الأمان المسموحة لدورك الحالي.',
                            'message' => "Query on table ($targetTable) violates allowed_query_conditions for role: $userRole."
                        ], 403);
                    }
                }
            }
        }

        // =========================================================================
        // 🛡️ طبقة الأمان الثالثة: الرفض الصارم للمستخدمين المقيدين ببياناتهم الشخصية فقط (enforce_self_only)
        // =========================================================================
        $enforceSelfOnly = $roleConstraints['enforce_self_only'] ?? true;

        if ($enforceSelfOnly) {
            // حظر عمليات الـ UPDATE المشبوهة أو التي لا تضمن تعديل نفس السجل الشخصي (مثل WHERE 1=1)
            if (str_contains($sqlUpper, 'UPDATE')) {
                $expectedCondition = "{$userIdentifierColumn} = '{$userIdentifier}'";
                $expectedConditionNoQuotes = "{$userIdentifierColumn} = {$userIdentifier}";
                
                if (stripos($sql, $expectedCondition) === false && stripos($sql, $expectedConditionNoQuotes) === false) {
                    return response()->json([
                        'type' => 'error',
                        'reply' => 'عذراً، لا يمكنك تعديل بيانات مستخدمين آخرين أو إجراء تعديل جماعي.',
                        'message' => 'Unauthorized UPDATE attempt blocked by enforce_self_only guard.'
                    ], 403);
                }
            } 
            // حظر استعلامات الـ SELECT التي تحاول سحب بيانات مستخدمين آخرين من الجداول الحساسة كـ users و enrollments
            elseif (str_contains($sqlUpper, 'SELECT')) {
                if (stripos($sql, 'users') !== false || stripos($sql, 'enrollments') !== false) {
                    $expectedPattern = "/({$userIdentifierColumn}|student_id)\s*=\s*['\"]?{$userIdentifier}['\"]?/i";
                    if (!preg_match($expectedPattern, $sql)) {
                        return response()->json([
                            'type' => 'error',
                            'reply' => 'عذراً، لا يمكنك عرض بيانات مستخدمين آخرين.',
                            'message' => 'Unauthorized data leak protection block by enforce_self_only guard.'
                        ], 403);
                    }
                }
            }
        }

        // --- 3. Role-Based Permissions Check ---
        $isOperationAllowed = false;
        $allowedOps = $roleConstraints['allowed_operations'] ?? ['SELECT'];

        foreach ($allowedOps as $op) {
            if (str_starts_with($sqlUpper, strtoupper(trim($op)))) {
                $isOperationAllowed = true;
                break;
            }
        }

        if (!$isOperationAllowed) {
            return response()->json([
                'type' => 'error', 
                'reply' => 'عذراً، دورك الحالي كـ (' . ($roleConfig['name'] ?? $userRole) . ') لا يمتلك الصلاحية لتنفيذ استعلامات من نوع (' . explode(' ', $sqlUpper)[0] . ').',
                'message' => 'Role-based permission denied.'
            ], 403);
        }

        // --- 5. Execution ---
        try {
            if (stripos(trim($sql), 'SELECT') === 0) {
                $result = DB::select($sql);
    
                if (empty($result)) {
                    return response()->json([
                        'type' => 'action', 
                        'reply' => 'لا توجد بيانات مطابقة لاستعلامك.',
                        'message' => 'لا توجد بيانات مطابقة لاستعلامك.',
                        'data' => []
                    ]);
                }

                return response()->json([
                    'type' => 'report', 
                    'reply' => 'إليك البيانات المطلوبة:',
                    'message' => 'تم جلب البيانات.', 
                    'data' => $result
                ]);
            } else {
                $affectedRows = DB::affectingStatement($sql);
                if (config('operation-gpt.logging', true)) {
                    Log::info("Final SQL Executed: " . $sql);
                }

                if ($affectedRows === 0) {
                    return response()->json([
                        'type' => 'error', 
                        'reply' => 'لم يتم تحديث أي سجل. قد تكون البيانات مطابقة أو لا توجد صلاحية.',
                        'message' => 'لم يتم تحديث أي سجل.'
                    ], 404);
                }

                return response()->json([
                    'type' => 'action', 
                    'reply' => 'تم تنفيذ العملية بنجاح.',
                    'message' => 'تم التحديث بنجاح.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("SQL Failure: " . $e->getMessage() . " | SQL: " . $sql);
            return response()->json([
                'type' => 'error', 
                'reply' => 'حدث خطأ في قاعدة البيانات أثناء محاولة التنفيذ.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


  
}


