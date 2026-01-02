<?php
/**
 * Logout
 * Destroy session and redirect to login
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

initSession();
logout();
redirect(APP_URL . '/login.php');
