<?php
/**
 * AJAX Email Availability Check
 */
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../config/db_connection.php';
require_once __DIR__ . '/../../../middleware/auth_middleware.php';

// Ensure user is admin
initSession();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$email = $_GET['email'] ?? '';
$school_id = $_SESSION['school_id'] ?? null;
$exclude_id = $_GET['exclude_id'] ?? null;

if (empty($email) || !$school_id) {
    echo json_encode(['available' => true]);
    exit;
}

try {
    $sql = "SELECT id FROM users WHERE email = ? AND school_id = ? AND deleted_at IS NULL";
    $params = [$email, $school_id];
    
    if ($exclude_id) {
        $sql .= " AND id != ?";
        $params[] = $exclude_id;
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exists = $stmt->fetch();
    
    header('Content-Type: application/json');
    echo json_encode(['available' => !$exists]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
