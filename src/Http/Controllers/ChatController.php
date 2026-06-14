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
                'reply' => 'Please login first.',
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

            // Intercept smart rejection messages from the AI
            if (!empty($data['error'])) {
                return response()->json([
                    'type' => 'error',
                    'reply' => $data['error'],
                    'message' => $data['error']
                ], 403);
            }

            $sql = $data['sql_query'] ?? null;

            if (!$sql) {
                Log::error("Failed AI Response: " . $aiResponse);
                return response()->json([
                    'type' => 'error', 
                    'reply' => 'No query was generated. Please rephrase your request more clearly.',
                    'message' => 'No query was generated.'
                ], 422);
            }

            return $this->executeQuerySecurely($sql);

        } catch (\Exception $e) {
            Log::error("ChatController Error: " . $e->getMessage());
            return response()->json([
                'type' => 'error',
                'reply' => 'A system error occurred. Please try again later.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function executeQuerySecurely($sql)
    {
        // Fetch authenticated user data and identifiers
        $user = Auth::user(); 
        $userIdentifierColumn = $user->getAuthIdentifierName();
        $userIdentifier = $user->getAuthIdentifier(); 
        $userRole = $user->role ?? 'user';

        // Fetch the configuration array for the current user's role
        $roleConfigs = config('operation-gpt-prompts.roles', []);
        $roleConfig = $roleConfigs[$userRole] ?? ($roleConfigs['user'] ?? []);
        $roleConstraints = $roleConfig['constraints'] ?? [];

        // =========================================================================
        // 🛡️ First Security Layer: Allowed Tables Whitelist Check
        // =========================================================================
        $allowedTables = $roleConstraints['allowed_tables'] ?? []; 
        
        // Extract requested table names from the query using a general Regex pattern
        $tablePattern = '/(?:FROM|JOIN|UPDATE|INTO)\s+[`"\'\s]*([a-zA-Z0-9_]+)/i';
        preg_match_all($tablePattern, $sql, $matches);
        $requestedTables = array_unique(array_map('strtolower', $matches[1] ?? []));

        // Immediately block the query if it requests a table not listed in this role's whitelist
        foreach ($requestedTables as $table) {
            if (!in_array($table, array_map('strtolower', $allowedTables))) {
                return response()->json([
                    'type' => 'error',
                    'reply' => 'Sorry, you do not have permission to access one of the requested tables in this query.',
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
        // 🛡️ Second Security Layer: General Dynamic Conditions Check (to prevent modifying other users)
        // =========================================================================
        $allowedQueryConditions = $roleConstraints['allowed_query_conditions'] ?? [];

        // Apply protection to all queries except INSERT to avoid logical errors in text matching
        if (!empty($allowedQueryConditions) && !str_starts_with($sqlUpper, 'INSERT')) {
            foreach ($allowedQueryConditions as $targetTable => $conditions) {
                if (stripos($sql, $targetTable) !== false) {
                    $hasValidCondition = false;

                    // Completely clean the current query text from spaces and quotes for a fair comparison
                    $cleanSql = str_replace([' ', "'", '"', '`'], '', $sql);

                    foreach ($conditions as $condition) {
                        // Replace the :identifier placeholder with the current user's actual identifier
                        $parsedCondition = str_replace(':identifier', $userIdentifier, $condition);
                        
                        // Also clean the expected condition from spaces and quotes
                        $cleanCondition = str_replace([' ', "'", '"', '`'], '', $parsedCondition);

                        // If the safe condition is found within the query text
                        if (stripos($cleanSql, $cleanCondition) !== false) {
                            $hasValidCondition = true;
                            break; 
                        }
                    }

                    // Immediately block the intrusion if the query does not meet any allowed condition in the Config for the target table
                    if (!$hasValidCondition) {
                        return response()->json([
                            'type' => 'error',
                            'reply' => 'Sorry, the query does not meet the allowed security conditions for your current role.',
                            'message' => "Query on table ($targetTable) violates allowed_query_conditions for role: $userRole."
                        ], 403);
                    }
                }
            }
        }

        // =========================================================================
        // 🛡️ Third Security Layer: Strict Rejection for Users Restricted to Their Own Data Only (enforce_self_only)
        // =========================================================================
        $enforceSelfOnly = $roleConstraints['enforce_self_only'] ?? true;

        if ($enforceSelfOnly) {
            // Block suspicious UPDATE operations or those that do not guarantee modifying the same personal record (e.g., WHERE 1=1)
            if (str_contains($sqlUpper, 'UPDATE')) {
                $expectedCondition = "{$userIdentifierColumn} = '{$userIdentifier}'";
                $expectedConditionNoQuotes = "{$userIdentifierColumn} = {$userIdentifier}";
                
                if (stripos($sql, $expectedCondition) === false && stripos($sql, $expectedConditionNoQuotes) === false) {
                    return response()->json([
                        'type' => 'error',
                        'reply' => 'Sorry, you cannot modify other users\' data or perform a mass update.',
                        'message' => 'Unauthorized UPDATE attempt blocked by enforce_self_only guard.'
                    ], 403);
                }
            } 
            // Block SELECT queries that attempt to pull data of other users from sensitive tables like users and enrollments
            elseif (str_contains($sqlUpper, 'SELECT')) {
                if (stripos($sql, 'users') !== false || stripos($sql, 'enrollments') !== false) {
                    $expectedPattern = "/({$userIdentifierColumn}|student_id)\s*=\s*['\"]?{$userIdentifier}['\"]?/i";
                    if (!preg_match($expectedPattern, $sql)) {
                        return response()->json([
                            'type' => 'error',
                            'reply' => 'Sorry, you cannot view other users\' data.',
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
                'reply' => 'Sorry, your current role as (' . ($roleConfig['name'] ?? $userRole) . ') does not have permission to execute queries of type (' . explode(' ', $sqlUpper)[0] . ').',
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
                        'reply' => 'No data matches your query.',
                        'message' => 'No data matches your query.',
                        'data' => []
                    ]);
                }

                return response()->json([
                    'type' => 'report', 
                    'reply' => 'Here is the requested data:',
                    'message' => 'Data fetched successfully.', 
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
                        'reply' => 'No records were updated. The data might be identical or you lack permission.',
                        'message' => 'No records were updated.'
                    ], 404);
                }

                return response()->json([
                    'type' => 'action', 
                    'reply' => 'Operation executed successfully.',
                    'message' => 'Updated successfully.'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("SQL Failure: " . $e->getMessage() . " | SQL: " . $sql);
            return response()->json([
                'type' => 'error', 
                'reply' => 'A database error occurred while attempting execution.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
