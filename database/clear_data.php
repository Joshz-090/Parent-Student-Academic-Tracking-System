<?php
/**
 * Maintenance Script: System Data Reset
 * PERMANENTLY DELETES Students, Parents, Teachers, Classes, and all transactional data.
 * PRESERVES Admin accounts.
 */

// Only require auth if triggered via web
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../middleware/auth_middleware.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Only allow full admins
    requireRole('admin');

    $user = getCurrentUser();
    if ($user['role'] !== 'admin') {
        die("Access Denied: Only Super Admins can perform a reset.");
    }
} else {
    // Basic database connection for CLI
    require_once __DIR__ . '/../config/db_connection.php';
}

echo "Starting System Data Reset...\n";

try {
    $pdo->beginTransaction();

    // 1. Clear Tracer Activities
    echo "Clearing Tracer Activities...\n";
    $pdo->exec("DELETE FROM tracer_activities");

    // 2. Clear Attendance
    echo "Clearing Attendance...\n";
    $pdo->exec("DELETE FROM attendance");

    // 3. Clear Grades
    echo "Clearing Grades...\n";
    $pdo->exec("DELETE FROM grades");

    // 4. Clear Remarks
    echo "Clearing Remarks...\n";
    $pdo->exec("DELETE FROM remarks");

    // 5. Clear Student-Class Enrollments
    echo "Clearing Enrollments...\n";
    $pdo->exec("DELETE FROM student_classes");

    // 6. Clear Classes
    echo "Clearing Classes...\n";
    $pdo->exec("DELETE FROM classes");

    // 7. Clear Students
    echo "Clearing Students...\n";
    $pdo->exec("DELETE FROM students");

    // 8. Clear non-admin Users
    echo "Clearing Teachers and Parents...\n";
    $pdo->exec("DELETE FROM users WHERE role != 'admin'");

    $pdo->commit();
    
    if (isset($_GET['web_trigger'])) {
        header('Location: ../public/admin/dashboard.php?reset_success=1');
        exit;
    }
    
    echo "\nSUCCESS: System data has been reset. Admin accounts preserved.\n";

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "\nFAILURE: " . $e->getMessage() . "\n";
    exit(1);
}
