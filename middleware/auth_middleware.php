<?php
/**
 * Authentication Middleware
 * Route protection based on authentication and roles
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

/**
 * Require user to be authenticated
 */
function requireAuth() {
    initSession();
    checkSessionTimeout();
    
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
}

/**
 * Require user to have specific role
 */
function requireRole($role) {
    requireAuth();
    
    if (!hasRole($role)) {
        http_response_code(403);
        die('Access Denied: You do not have permission to access this page.');
    }
}

/**
 * Require user to have any of the specified roles
 */
function requireAnyRole($roles) {
    requireAuth();
    
    if (!hasAnyRole($roles)) {
        http_response_code(403);
        die('Access Denied: You do not have permission to access this page.');
    }
}

/**
 * Require user to belong to specific school
 */
function requireSchool($school_id) {
    requireAuth();
    
    if (getCurrentSchoolId() != $school_id) {
        http_response_code(403);
        die('Access Denied: You cannot access data from another school.');
    }
}

/**
 * Redirect if already logged in
 */
function redirectIfLoggedIn() {
    initSession();
    
    if (isLoggedIn()) {
        $role = $_SESSION['user_role'];
        
        switch ($role) {
            case 'admin':
                header('Location: ' . APP_URL . '/admin/dashboard.php');
                break;
            case 'student_admin':
                header('Location: ' . APP_URL . '/admin/manage_students.php');
                break;
            case 'teacher':
                header('Location: ' . APP_URL . '/teacher/dashboard.php');
                break;
            case 'parent':
                header('Location: ' . APP_URL . '/parent/dashboard.php');
                break;
        }
        exit;
    }
}
