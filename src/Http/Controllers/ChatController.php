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
        $isAdmin = isset($user->role) && ($user->role === 'admin' || $user->role === 'super_admin');
        if (!$isAdmin && str_contains(strtoupper($sql), 'UPDATE')) {
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
                $htmlTable = $this->generateHtmlTable($result);
                
                return response()->json([
                    'type' => 'report', 
                    'reply' => $htmlTable,
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

    private function generateHtmlTable($data)
    {
        if (empty($data)) {
            return '<p style="color: var(--text-muted); margin-top: 10px;">لا توجد بيانات مطابقة.</p>';
        }

        $html = '<div style="overflow-x: auto; margin-top: 15px;"><table class="report-table" style="width: 100%; border-collapse: separate; border-spacing: 0; border-radius: 12px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.1);">';
        
        // Headers
        $firstRow = (array) $data[0];
        $html .= '<thead><tr>';
        foreach (array_keys($firstRow) as $header) {
            $html .= '<th style="background: rgba(255, 255, 255, 0.1); padding: 12px; text-align: right; font-size: 0.85rem; color: #94a3b8;">' . htmlspecialchars($header) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        // Rows
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ((array) $row as $value) {
                // If value is null, make it an empty string
                $displayValue = $value === null ? '' : (string) $value;
                $html .= '<td style="padding: 12px; border-top: 1px solid rgba(255, 255, 255, 0.05); font-size: 0.9rem;">' . htmlspecialchars($displayValue) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }
}


