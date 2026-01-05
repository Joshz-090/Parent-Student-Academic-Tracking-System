<?php
/**
 * Helper Functions
 * Utility functions for the application
 */

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format date time
 */
function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

/**
 * Calculate attendance rate
 */
function calculateAttendanceRate($student_id, $date_from = null, $date_to = null) {
    global $pdo;
    
    try {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
                FROM attendance 
                WHERE student_id = ?";
        
        $params = [$student_id];
        
        if ($date_from && $date_to) {
            $sql .= " AND date BETWEEN ? AND ?";
            $params[] = $date_from;
            $params[] = $date_to;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        if ($result['total'] == 0) return 0;
        
        return round(($result['present'] / $result['total']) * 100, 2);
        
    } catch (PDOException $e) {
        error_log("Attendance Rate Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calculate GPA
 */
function calculateGPA($student_id, $quarter = null) {
    global $pdo;
    
    try {
        $sql = "SELECT AVG(score) as gpa FROM grades WHERE student_id = ?";
        $params = [$student_id];
        
        if ($quarter) {
            $sql .= " AND quarter = ?";
            $params[] = $quarter;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        
        return round($result['gpa'] ?? 0, 2);
        
    } catch (PDOException $e) {
        error_log("GPA Calculation Error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get grade letter from score
 */
function getGradeLetter($score) {
    if ($score >= 90) return 'A';
    if ($score >= 85) return 'A-';
    if ($score >= 80) return 'B+';
    if ($score >= 75) return 'B';
    if ($score >= 70) return 'B-';
    if ($score >= 65) return 'C+';
    if ($score >= 60) return 'C';
    if ($score >= 55) return 'C-';
    if ($score >= 50) return 'D';
    return 'F';
}

/**
 * Upload file securely
 */
function uploadFile($file, $directory = 'general') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No file uploaded or upload error'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File size exceeds maximum allowed'];
    }
    
    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'File type not allowed'];
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $upload_path = UPLOAD_DIR . $directory . '/';
    
    // Create directory if it doesn't exist
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0755, true);
    }
    
    $destination = $upload_path . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $filename,
            'path' => $directory . '/' . $filename
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Get school branding
 */
function getSchoolBranding($school_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT school_name, logo, primary_color, footer_text, academic_year 
            FROM schools 
            WHERE id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$school_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get School Branding Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get default school branding (for login/landing pages)
 */
function getDefaultSchoolBranding() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("
            SELECT id, school_name, logo, primary_color, footer_text, academic_year 
            FROM schools 
            WHERE deleted_at IS NULL 
            ORDER BY id ASC 
            LIMIT 1
        ");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get Default School Branding Error: " . $e->getMessage());
        return null;
    }
}

/**
 * Generate tracer activity manually
 */
function generateTracerActivity($student_id, $type, $title, $description = null, $icon = 'info', $color = 'blue', $reference_id = null, $reference_table = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO tracer_activities 
            (student_id, activity_type, title, description, icon, color, reference_id, reference_table, activity_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE())
        ");
        
        return $stmt->execute([
            $student_id, $type, $title, $description, $icon, $color, $reference_id, $reference_table
        ]);
    } catch (PDOException $e) {
        error_log("Generate Tracer Activity Error: " . $e->getMessage());
        return false;
    }
}

/**
 * JSON response
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Convert Hex color to RGB string (e.g., "59, 130, 246")
 */
function hexToRgb($hex) {
    $hex = str_replace("#", "", $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return "$r, $g, $b";
}

/**
 * Get contrast color (black or white) based on luminance of hex color
 */
function getContrastColor($hex) {
    $hex = str_replace("#", "", $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    return $luminance > 0.7 ? '#1f2937' : '#ffffff';
}

/**
 * Human readable time elapsed
 */
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = (array) $now->diff($ago);

    $diff['w'] = floor($diff['d'] / 7);
    $diff['d'] -= $diff['w'] * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff[$k]) {
            $v = $diff[$k] . ' ' . $v . ($diff[$k] > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}

/**
 * Get the next sequential Student Code globally
 */
function getNextStudentCode() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            SELECT student_code 
            FROM students 
            WHERE student_code REGEXP '^[0-9]+$'
            ORDER BY CAST(student_code AS UNSIGNED) DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $last_code = $stmt->fetchColumn();
        
        if (!$last_code) return "1001";
        
        return (string)((int)$last_code + 1);
    } catch (PDOException $e) {
        return "1001";
    }
}

/**
 * Validate Ethiopian Grade Level (5-8)
 */
function isValidEthiopianGrade($grade) {
    // Extract number if it's like "Grade 5"
    if (preg_match('/(\d+)/', $grade, $matches)) {
        $num = (int)$matches[1];
        return ($num >= 5 && $num <= 8);
    }
    return false;
}
