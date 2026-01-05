<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

initSession();

if (isLoggedIn()) {
    $role = $_SESSION['user_role'];
    
    switch ($role) {
        case 'admin':
            redirect(APP_URL . '/admin/dashboard.php');
            break;
        case 'teacher':
            redirect(APP_URL . '/teacher/dashboard.php');
            break;
        case 'parent':
            redirect(APP_URL . '/parent/dashboard.php');
            break;
        default:
            redirect(APP_URL . '/login.php');
    }
} else {
    redirect(APP_URL . '/login.php');
}
