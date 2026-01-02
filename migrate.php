<?php
/**
 * Database Migration Script
 */
require_once __DIR__ . '/config/db_connection.php';

try {
    echo "Starting migration...\n";

    // 1. Add 'student_admin' to the role ENUM in users table
    // Note: MySQL doesn't support easy ALTER for ENUM without rewriting the column.
    // We'll use a safer approach for MySQL 8.0+
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'teacher', 'parent', 'student_admin') NOT NULL");
    echo "Added 'student_admin' role to users table.\n";

    // 2. Add 'age' column to students table
    // Check if column exists first to avoid error
    $checkColumn = $pdo->query("SHOW COLUMNS FROM students LIKE 'age'");
    if ($checkColumn->rowCount() == 0) {
        $pdo->exec("ALTER TABLE students ADD COLUMN age INT UNSIGNED AFTER section");
        echo "Added 'age' column to students table.\n";
    } else {
        echo "'age' column already exists in students table.\n";
    }

    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
