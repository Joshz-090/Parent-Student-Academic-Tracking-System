-- ============================================================================
-- Multi-School Parent-Student Tracer System - Database Schema
-- Version: 1.0
-- PHP 8.0+ | MySQL 8.0+
-- ============================================================================

DROP DATABASE IF EXISTS ps_tracer;
CREATE DATABASE ps_tracer CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ps_tracer;

-- ============================================================================
-- CORE TABLES
-- ============================================================================

-- Schools Table (Multi-school support)
CREATE TABLE schools (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    primary_color VARCHAR(7) DEFAULT '#3B82F6',
    footer_text TEXT DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT '2024-2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB;

-- Users Table (Admin, Teacher, Parent)
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'parent') NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_school (email, school_id, deleted_at),
    INDEX idx_school_role (school_id, role),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB;

-- Students Table
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    parent_id INT UNSIGNED DEFAULT NULL,
    student_code VARCHAR(50) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    section VARCHAR(10) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_student_code (student_code, school_id),
    INDEX idx_school_grade (school_id, grade_level),
    INDEX idx_parent (parent_id),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB;

-- Classes/Subjects Table
CREATE TABLE classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED DEFAULT NULL,
    subject_name VARCHAR(100) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    section VARCHAR(10) DEFAULT NULL,
    schedule VARCHAR(255) DEFAULT NULL,
    room_number VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_school_teacher (school_id, teacher_id),
    INDEX idx_grade_section (grade_level, section),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB;

-- Student-Class Enrollment (Many-to-Many)
CREATE TABLE student_classes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, class_id)
) ENGINE=InnoDB;

-- ============================================================================
-- ACADEMIC TABLES
-- ============================================================================

-- Attendance Table
CREATE TABLE attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    date DATE NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') NOT NULL DEFAULT 'present',
    notes TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (student_id, class_id, date),
    INDEX idx_student_date (student_id, date),
    INDEX idx_date_status (date, status)
) ENGINE=InnoDB;

-- Grades Table
CREATE TABLE grades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    quarter ENUM('Q1', 'Q2', 'Q3', 'Q4') NOT NULL,
    score DECIMAL(5,2) NOT NULL,
    grade_letter VARCHAR(2) DEFAULT NULL,
    final_grade DECIMAL(5,2) DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_grade (student_id, class_id, quarter),
    INDEX idx_student_class (student_id, class_id),
    INDEX idx_quarter (quarter)
) ENGINE=InnoDB;

-- Remarks Table (Teacher Comments)
CREATE TABLE remarks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    teacher_id INT UNSIGNED NOT NULL,
    remark_type ENUM('academic', 'behavioral', 'general') NOT NULL DEFAULT 'general',
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_teacher (teacher_id),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB;

-- ============================================================================
-- TRACER FEATURE
-- ============================================================================

-- Tracer Activities Table (Auto-generated timeline events)
CREATE TABLE tracer_activities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    activity_type ENUM('attendance', 'grade', 'remark', 'achievement', 'alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'info',
    color VARCHAR(20) DEFAULT 'blue',
    reference_id INT UNSIGNED DEFAULT NULL,
    reference_table VARCHAR(50) DEFAULT NULL,
    activity_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_date (student_id, activity_date DESC),
    INDEX idx_type (activity_type),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB;

-- ============================================================================
-- TRIGGERS FOR AUTO-GENERATING TRACER ACTIVITIES
-- ============================================================================

DELIMITER $$

-- Trigger: Attendance -> Tracer Activity
CREATE TRIGGER after_attendance_insert
AFTER INSERT ON attendance
FOR EACH ROW
BEGIN
    DECLARE activity_title VARCHAR(255);
    DECLARE activity_icon VARCHAR(50);
    DECLARE activity_color VARCHAR(20);
    
    IF NEW.status = 'absent' THEN
        SET activity_title = 'Absent from class';
        SET activity_icon = 'x-circle';
        SET activity_color = 'red';
    ELSEIF NEW.status = 'late' THEN
        SET activity_title = 'Arrived late to class';
        SET activity_icon = 'clock';
        SET activity_color = 'yellow';
    ELSEIF NEW.status = 'excused' THEN
        SET activity_title = 'Excused absence';
        SET activity_icon = 'check-circle';
        SET activity_color = 'blue';
    ELSE
        SET activity_title = NULL; -- Don't create activity for 'present'
    END IF;
    
    IF activity_title IS NOT NULL THEN
        INSERT INTO tracer_activities (
            student_id, activity_type, title, description, icon, color, 
            reference_id, reference_table, activity_date
        ) VALUES (
            NEW.student_id, 'attendance', activity_title, NEW.notes, 
            activity_icon, activity_color, NEW.id, 'attendance', NEW.date
        );
    END IF;
END$$

-- Trigger: Grades -> Tracer Activity
CREATE TRIGGER after_grade_insert
AFTER INSERT ON grades
FOR EACH ROW
BEGIN
    DECLARE activity_title VARCHAR(255);
    DECLARE activity_icon VARCHAR(50);
    DECLARE activity_color VARCHAR(20);
    DECLARE subject_name VARCHAR(100);
    
    SELECT c.subject_name INTO subject_name 
    FROM classes c WHERE c.id = NEW.class_id LIMIT 1;
    
    IF NEW.score >= 90 THEN
        SET activity_title = CONCAT('Excellent! Scored ', NEW.score, ' in ', subject_name);
        SET activity_icon = 'star';
        SET activity_color = 'green';
    ELSEIF NEW.score >= 75 THEN
        SET activity_title = CONCAT('Good performance: ', NEW.score, ' in ', subject_name);
        SET activity_icon = 'trending-up';
        SET activity_color = 'blue';
    ELSEIF NEW.score >= 50 THEN
        SET activity_title = CONCAT('Average score: ', NEW.score, ' in ', subject_name);
        SET activity_icon = 'minus-circle';
        SET activity_color = 'yellow';
    ELSE
        SET activity_title = CONCAT('Needs improvement: ', NEW.score, ' in ', subject_name);
        SET activity_icon = 'alert-triangle';
        SET activity_color = 'red';
    END IF;
    
    INSERT INTO tracer_activities (
        student_id, activity_type, title, description, icon, color, 
        reference_id, reference_table, activity_date
    ) VALUES (
        NEW.student_id, 'grade', activity_title, 
        CONCAT('Quarter: ', NEW.quarter), 
        activity_icon, activity_color, NEW.id, 'grades', CURDATE()
    );
END$$

-- Trigger: Remarks -> Tracer Activity
CREATE TRIGGER after_remark_insert
AFTER INSERT ON remarks
FOR EACH ROW
BEGIN
    DECLARE activity_title VARCHAR(255);
    DECLARE teacher_name VARCHAR(255);
    
    SELECT name INTO teacher_name FROM users WHERE id = NEW.teacher_id LIMIT 1;
    
    SET activity_title = CONCAT('Teacher remark: ', NEW.remark_type);
    
    INSERT INTO tracer_activities (
        student_id, activity_type, title, description, icon, color, 
        reference_id, reference_table, activity_date
    ) VALUES (
        NEW.student_id, 'remark', activity_title, 
        CONCAT(teacher_name, ': ', LEFT(NEW.comment, 100)), 
        'message-square', 'purple', NEW.id, 'remarks', CURDATE()
    );
END$$

DELIMITER ;

-- ============================================================================
-- VIEWS FOR COMMON QUERIES
-- ============================================================================

-- View: Student Full Details
CREATE VIEW v_student_details AS
SELECT 
    s.id,
    s.school_id,
    s.student_code,
    CONCAT(s.first_name, ' ', s.last_name) AS full_name,
    s.first_name,
    s.last_name,
    s.date_of_birth,
    s.gender,
    s.grade_level,
    s.section,
    s.photo,
    u.name AS parent_name,
    u.email AS parent_email,
    u.phone AS parent_phone,
    sc.school_name,
    s.created_at,
    s.deleted_at
FROM students s
LEFT JOIN users u ON s.parent_id = u.id
LEFT JOIN schools sc ON s.school_id = sc.id;

-- View: Attendance Summary
CREATE VIEW v_attendance_summary AS
SELECT 
    student_id,
    COUNT(*) AS total_records,
    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count,
    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) AS late_count,
    SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) AS excused_count,
    ROUND((SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) AS attendance_rate
FROM attendance
GROUP BY student_id;

-- ============================================================================
-- SAMPLE DATA (For Testing)
-- ============================================================================

-- Insert Sample School
INSERT INTO schools (school_name, logo, primary_color, footer_text, academic_year) VALUES
('Demo High School', 'demo_logo.png', '#3B82F6', 'Excellence in Education', '2024-2025');

-- Insert Sample Admin
INSERT INTO users (school_id, name, email, password, role, phone) VALUES
(1, 'Admin User', 'admin@demo.school', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '+251911234567');
-- Password: password

-- Insert Sample Teachers
INSERT INTO users (school_id, name, email, password, role, phone) VALUES
(1, 'John Teacher', 'teacher1@demo.school', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', '+251911234568'),
(1, 'Jane Educator', 'teacher2@demo.school', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher', '+251911234569');

-- Insert Sample Parents
INSERT INTO users (school_id, name, email, password, role, phone) VALUES
(1, 'Parent One', 'parent1@demo.school', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', '+251911234570'),
(1, 'Parent Two', 'parent2@demo.school', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent', '+251911234571');

-- Insert Sample Students
INSERT INTO students (school_id, parent_id, student_code, first_name, last_name, date_of_birth, gender, grade_level, section) VALUES
(1, 4, 'STU001', 'Alice', 'Johnson', '2010-05-15', 'female', 'Grade 9', 'A'),
(1, 4, 'STU002', 'Bob', 'Johnson', '2012-08-22', 'male', 'Grade 7', 'B'),
(1, 5, 'STU003', 'Charlie', 'Smith', '2011-03-10', 'male', 'Grade 8', 'A');

-- Insert Sample Classes
INSERT INTO classes (school_id, teacher_id, subject_name, grade_level, section, schedule, room_number) VALUES
(1, 2, 'Mathematics', 'Grade 9', 'A', 'Mon/Wed/Fri 8:00-9:00', '101'),
(1, 2, 'Physics', 'Grade 9', 'A', 'Tue/Thu 8:00-9:00', '102'),
(1, 3, 'English', 'Grade 9', 'A', 'Mon/Wed/Fri 9:00-10:00', '103');

-- Enroll Students in Classes
INSERT INTO student_classes (student_id, class_id) VALUES
(1, 1), (1, 2), (1, 3),
(2, 1), (2, 3),
(3, 1), (3, 2), (3, 3);

-- Insert Sample Attendance (will trigger tracer activities)
INSERT INTO attendance (student_id, class_id, date, status, recorded_by) VALUES
(1, 1, '2024-12-27', 'present', 2),
(1, 1, '2024-12-28', 'late', 2),
(1, 1, '2024-12-29', 'absent', 2),
(2, 1, '2024-12-27', 'present', 2),
(2, 1, '2024-12-28', 'present', 2);

-- Insert Sample Grades (will trigger tracer activities)
INSERT INTO grades (student_id, class_id, quarter, score, grade_letter, recorded_by) VALUES
(1, 1, 'Q1', 92.5, 'A', 2),
(1, 2, 'Q1', 88.0, 'B+', 2),
(1, 3, 'Q1', 95.0, 'A', 3),
(2, 1, 'Q1', 78.5, 'B', 2);

-- Insert Sample Remarks (will trigger tracer activities)
INSERT INTO remarks (student_id, teacher_id, remark_type, comment) VALUES
(1, 2, 'academic', 'Excellent performance in mathematics. Shows great problem-solving skills.'),
(1, 3, 'behavioral', 'Very participative in class discussions. A role model for peers.'),
(2, 2, 'academic', 'Good effort, but needs to focus more on homework completion.');

-- ============================================================================
-- INDEXES FOR PERFORMANCE
-- ============================================================================

-- Additional composite indexes for common queries
CREATE INDEX idx_attendance_student_date_status ON attendance(student_id, date, status);
CREATE INDEX idx_grades_student_class_quarter ON grades(student_id, class_id, quarter);
CREATE INDEX idx_tracer_student_created ON tracer_activities(student_id, created_at DESC);

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
