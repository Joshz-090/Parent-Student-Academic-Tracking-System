<?php

namespace App\Controllers;

use App\Services\TracerService;

class TracerController
{
    private TracerService $tracerService;

    public function __construct(TracerService $tracerService)
    {
        $this->tracerService = $tracerService;
    }

    public function getTimeline(int $studentId, string $filter = 'all'): void
    {
        try {
            $activities = $this->tracerService->getHistory($studentId, $filter);
            $student = $this->tracerService->getStudent($studentId);

            $this->jsonResponse([
                'success' => true,
                'student' => $student,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
