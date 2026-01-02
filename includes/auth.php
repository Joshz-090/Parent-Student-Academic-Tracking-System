<?php
/**
 * Authentication Functions
 * Secure user authentication and authorization
 */

require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/session.php';

/**
 * Hash password securely
 */
function hashPassword($password) {
    return password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
}

/**
 * Login user
 */
function login($identifier, $password) {
    global $pdo;
    
    try {
        // First try finding user by email
        $stmt = $pdo->prepare("
            SELECT id, school_id, name, email, password, role 
            FROM users 
            WHERE email = ? AND deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$identifier]);
        $user = $stmt->fetch();
        
        // If not found by email, see if it's a Student ID for a parent
        if (!$user) {
            $stmt = $pdo->prepare("
                SELECT u.id, u.school_id, u.name, u.email, u.password, u.role
                FROM users u
                JOIN students s ON u.id = s.parent_id
                WHERE s.student_code = ? AND u.role = 'parent' AND u.deleted_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([$identifier]);
            $user = $stmt->fetch();
        }
        
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['school_id'] = $user['school_id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            return [
                'success' => true,
                'role' => $user['role'],
                'message' => 'Login successful'
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Invalid credentials'
        ];
        
    } catch (PDOException $e) {
        error_log("Login Error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

/**
 * Logout user
 */
function logout() {
    destroySession();
    return true;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isLoggedIn()) return false;
    return in_array($_SESSION['user_role'], $roles);
}

/**
 * Get current user data
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    return [
        'id' => $_SESSION['user_id'],
        'school_id' => $_SESSION['school_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Get current school ID
 */
function getCurrentSchoolId() {
    return $_SESSION['school_id'] ?? null;
}

/**
 * Check session timeout
 */
function checkSessionTimeout() {
    if (isLoggedIn()) {
        $timeout = SESSION_LIFETIME;
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            logout();
            return false;
        }
        $_SESSION['last_activity'] = time();
    }
    return true;
}
