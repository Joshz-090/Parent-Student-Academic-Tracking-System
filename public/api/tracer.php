<?php
/**
 * AJAX API Endpoint for Student Tracer
 */

require_once __DIR__ . '/../../src/Config/config.php';
require_once __DIR__ . '/../../autoload.php';

use App\Core\Database;
use App\Core\SessionManager;
use App\Services\TracerService;
use App\Controllers\TracerController;

// Initialize Infrastructure
$session = new SessionManager();
$user = $session->get('user');

if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Multi-School Isolation
$db = new Database($user['school_id']);
$tracerService = new TracerService($db);
$controller = new TracerController($tracerService);

// Handle Request
$studentId = (int)($_GET['student_id'] ?? 0);
$filter = $_GET['filter'] ?? 'all';

$controller->getTimeline($studentId, $filter);
