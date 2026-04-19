<?php

namespace OperationGpt\Services;

class OperationParser
{
    public function parse(string $aiResponse): array
    {
        $data = json_decode($aiResponse, true);

        if (!$data) {
            return [
                'type' => 'error',
                'message' => 'Invalid AI JSON response',
            ];
        }

        // تحقق من نوع العملية
        if (!isset($data['type']) || !in_array($data['type'], ['action', 'report', 'error'])) {
            return [
                'type' => 'error',
                'message' => 'Unknown type in AI response',
            ];
        }

        return $data;
    }
}