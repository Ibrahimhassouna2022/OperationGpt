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
        // authentication of the user
        $user = Auth::user(); 
        $userIdentifierColumn = $user->getAuthIdentifierName();
        $userIdentifier = $user->getAuthIdentifier(); 

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

        // --- 2. Identity Injection for Regular Users ---
        $roleConfigs = config('operation-gpt-prompts.roles', []);
        $userRole = $user->role ?? 'user';
        $roleConfig = $roleConfigs[$userRole] ?? ($roleConfigs['user'] ?? []);
        $roleConstraints = $roleConfig['constraints'] ?? [];
        
        $enforceSelfOnly = $roleConstraints['enforce_self_only'] ?? true;

        if ($enforceSelfOnly && str_contains(strtoupper($sql), 'UPDATE')) {
            if (stripos($sql, 'WHERE') !== false) {
                $sql = preg_replace('/WHERE\b.*/i', "WHERE {$userIdentifierColumn} = '$userIdentifier'", $sql);
            } else {
                $sql = rtrim(trim($sql), ';') . " WHERE {$userIdentifierColumn} = '$userIdentifier'";
            }
        }

        $sqlUpper = strtoupper(trim($sql));

        // --- 3. Role-Based Permissions Check ---
        // We rely entirely on the configuration to determine what is allowed.
        // If 'DROP' is in allowed_operations for a role, the system will permit it.
        $roleConfigs = config('operation-gpt-prompts.roles', []);
        $userRole = $user->role ?? 'user';
        $roleConfig = $roleConfigs[$userRole] ?? ($roleConfigs['user'] ?? []);
        $roleConstraints = $roleConfig['constraints'] ?? [];
        $allowedOps = $roleConstraints['allowed_operations'] ?? ['SELECT'];

        $isOperationAllowed = false;
        foreach ($allowedOps as $op) {
            if (str_starts_with($sqlUpper, strtoupper($op))) {
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


