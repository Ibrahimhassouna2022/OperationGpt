<?php

namespace OperationGpt\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use OperationGpt\Services\OpenAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
<<<<<<< HEAD
=======
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
>>>>>>> 926b9bd (تعديلات اخيرة)

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
<<<<<<< HEAD
    public function chat(Request $request)
    {
        try {
            $request->validate([
                'message' => 'required|string|max:1000',
            ]);

            $message = $request->input('message');

            // 1. Get AI Response
            $openAIService = new OpenAIService();
            $aiResponse = $openAIService->sendMessage($message);

            // 2. Decode Response
            $data = json_decode($aiResponse, true);

            if (isset($data['error'])) {
                return response()->json(['type' => 'error', 'message' => $data['error']], 422);
            }

            if (!isset($data['SQL query'])) {
                return response()->json(['type' => 'error', 'message' => 'Could not generate SQL query.'], 422);
            }

            $sql = $data['SQL query'];

            // 3. Execute SQL
            // Determine if it's a SELECT or an action
            if (stripos(trim($sql), 'SELECT') === 0) {
                $result = DB::select($sql);
                return response()->json([
                    'type' => 'report',
                    'message' => 'Query executed successfully.',
                    'data' => $result
                ]);
            } else {
                DB::statement($sql);
                return response()->json([
                    'type' => 'action',
                    'message' => 'Operation executed successfully.',
                    'data' => ['affected_rows' => 'Check database for changes']
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('OperationGpt Controller Error: ' . $e->getMessage());

            return response()->json([
                'type' => 'error',
                'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()
            ], 500);
        }
    }
}
=======
    // public function chat(Request $request)
    // {
    //     $data = [];
    //     try {
    //         $request->validate([
    //             'message' => 'required|string|max:1000',
    //         ]);

    //         $message = $request->input('message');

    //         // 1. Get AI Response
    //         $openAIService = new OpenAIService();
    //         $aiResponse = $openAIService->sendMessage($message);

    //         // 2. Decode Response
    //         $data = json_decode($aiResponse, true);

    //         if (isset($data['error'])) {
    //             return response()->json(['type' => 'error', 'message' => $data['error']], 422);
    //         }

    //         if (!isset($data['sql_query'])) {
    //             return response()->json(['type' => 'error', 'message' => 'Could not generate SQL query.'], 422);
    //         }

    //         $sql = $data['sql_query'];

    //         // 3. Execute SQL
    //         // Determine if it's a SELECT or an action
    //         if (stripos(trim($sql), 'SELECT') === 0) {
    //             $result = DB::select($sql);
    //             return response()->json([
    //                 'type' => 'report',
    //                 'message' => 'Query executed successfully.',
    //                 'data' => $result
    //             ]);
    //         } else {
    //             DB::statement($sql);
    //             return response()->json([
    //                 'type' => 'action',
    //                 'message' => 'Operation executed successfully.',
    //                 'data' => ['affected_rows' => 'Check database for changes']
    //             ]);
    //         }

    //     } catch (\Throwable $e) {
    //         Log::error('OperationGpt Controller Error: ' . $e->getMessage());

    //         return response()->json([
    //             'type' => 'error',
    //             'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function chat(Request $request)
    {
        try {
            $request->validate(['message' => 'required|string|max:1000']);
            $message = $request->input('message');

            // 1. طلب الاستعلام من OpenAI
            $openAIService = new \OperationGpt\Services\OpenAIService();
            // $aiResponse = $openAIService->sendMessage($message);
            
            // $data = json_decode($aiResponse, true);
            // $sql = $data['sql_query'] ?? null;

            // if (!$sql) {
            //     return response()->json(['type' => 'error', 'message' => 'لم يتم تكوين استعلام.'], 422);
            // }

            // 1. طلب الاستعلام من OpenAI
            $aiResponse = $openAIService->sendMessage($message);

            // تنظيف الرد من أي علامات Markdown قد يضيفها AI بالخطأ
            $cleanResponse = preg_replace('/```json|```/', '', $aiResponse);
            $data = json_decode(trim($cleanResponse), true);

            $sql = $data['sql_query'] ?? null;

            if (!$sql) {
                // سجل الرد الأصلي في الـ Log لنعرف لماذا فشل
                \Illuminate\Support\Facades\Log::error("Failed AI Response: " . $aiResponse);
                return response()->json(['type' => 'error', 'message' => 'لم يتم تكوين استعلام. الرد كان: ' . substr($aiResponse, 0, 50)], 422);
            }

            // 2. هنا "بيت القصيد": استدعاء دالة الفحص والتنفيذ في المشروع
            // مررنا الاستعلام SQL للدالة التي سنكتبها بالأسفل
            return $this->executeQuerySecurely($sql);

        } catch (\Exception $e) {
            // جلب الرد الكامل للخطأ إذا كان متوفراً
            if (method_exists($e, 'getResponse') && $e->getResponse()) {
                $fullError = $e->getResponse()->getBody()->getContents();
                \Illuminate\Support\Facades\Log::error("OpenAI Technical Error: " . $fullError);
                return $fullError; // سيعرض لك الخطأ الحقيقي في واجهة التشات (مثل Insufficient Balance)
            }
            
            return json_encode(['error' => $e->getMessage()]);
        }
    }

    private function executeQuerySecurely($sql)
{
    $user = Auth::user(); 
    $empNumber = $user->employee_number; 

    // --- 1. معالجة وتشفير كلمة السر (قبل أي شيء آخر) ---
    if (stripos($sql, 'password') !== false && stripos($sql, 'UPDATE') !== false) {
        $pattern = "/password\s*=\s*['\"]([^'\"]+)['\"]/i";
        if (preg_match($pattern, $sql, $matches)) {
            $plainPassword = $matches[1];
            $hashedPassword = Hash::make($plainPassword);
            // استبدال كلمة السر بنسختها المشفرة
            $sql = str_replace("'$plainPassword'", "'$hashedPassword'", $sql);
            $sql = str_replace("\"$plainPassword\"", "'$hashedPassword'", $sql);
        }
    }

    // --- 2. إصلاح الاستعلام وحقن الهوية (تصحيح خطأ البتر) ---
    if ($user->role !== 'admin' && str_contains(strtoupper($sql), 'UPDATE')) {
        if (stripos($sql, 'WHERE') !== false) {
            // نعدل الجزء الخاص بالـ WHERE فقط دون حذف إغلاق الاستعلام إذا وجد
            $sql = preg_replace('/WHERE\b.*/i', "WHERE employee_number = '$empNumber'", $sql);
        } else {
            // تنظيف الاستعلام من أي فاصلة منقوطة في النهاية قبل إضافة الـ WHERE
            $sql = rtrim(trim($sql), ';') . " WHERE employee_number = '$empNumber'";
        }
    }

    $sqlUpper = strtoupper($sql);

    // --- 3. فحص الصلاحيات ---
    if ($user->role !== 'admin') {
        if (str_contains($sqlUpper, 'DELETE') || str_contains($sqlUpper, 'INSERT') || str_contains($sqlUpper, 'DROP')) {
            return response()->json(['type' => 'error', 'message' => 'غير مسموح لك بالحذف أو الإضافة.'], 403);
        }

        $forbiddenFields = ['SALARY', 'ROLE', 'POSITION'];
        foreach ($forbiddenFields as $field) {
            if (str_contains($sqlUpper, $field)) {
                return response()->json(['type' => 'error', 'message' => 'لا تملك صلاحية تعديل الحقول الإدارية.'], 403);
            }
        }
    }

    // --- 4. التنفيذ ---
    try {
        if (stripos(trim($sql), 'SELECT') === 0) {
            $result = DB::select($sql);
            return response()->json(['type' => 'report', 'message' => 'تم جلب البيانات.', 'data' => $result]);
        } else {
            // تنفيذ الاستعلام وحفظه في الـ Log للتأكد
            $affectedRows = DB::affectingStatement($sql);
            Log::info("Final SQL Executed: " . $sql); // ستجده الآن كاملاً وغير مبتور

            if ($affectedRows === 0) {
                return response()->json(['type' => 'error', 'message' => 'لم يتم تحديث أي سجل.'], 404);
            }

            return response()->json(['type' => 'action', 'message' => 'تم تحديث بياناتك بنجاح.']);
        }
    } catch (\Exception $e) {
        Log::error("SQL Failure: " . $e->getMessage());
        return response()->json(['type' => 'error', 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
    }
}
}


>>>>>>> 926b9bd (تعديلات اخيرة)
