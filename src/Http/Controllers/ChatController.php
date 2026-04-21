<?php

namespace OperationGpt\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use OperationGpt\Services\OpenAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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