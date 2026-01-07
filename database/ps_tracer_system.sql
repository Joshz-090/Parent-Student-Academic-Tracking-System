-- ============================================================================
-- PROJECT: Parent-Student Academic Tracking System (PS-tracer)
-- PURPOSE: Academic Project Submission - DB Implementation
-- DEVELOPER: Joshz-090
-- GROUP NAME: [PLEASE INSERT GROUP NAME HERE]
-- DATE: 2026-01-07
-- ============================================================================
-- NOTE: This file contains the complete database schema and demo seed data.
-- Engine: InnoDB | Character Set: UTF8mb4
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 0. DATABASE INITIALIZATION
-- ----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS ps_tracer;
USE ps_tracer;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- 1. SCHOOLS (The Root of Isolation / Multi-tenancy)
-- ----------------------------------------------------------------------------
-- This table stores institutional metadata and branding tokens.
-- Every other entity in the system is isolated by school_id.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS schools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_name VARCHAR(255) NOT NULL,
    logo VARCHAR(255) DEFAULT NULL,
    primary_color VARCHAR(7) DEFAULT '#3B82F6',
    footer_text TEXT DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT '2025-2026',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_school_active (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. USERS (Role-Based Access Control)
-- ----------------------------------------------------------------------------
-- Handles authentication and role assignment (Admin, Teacher, Parent, Student).
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'teacher', 'parent', 'student') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_user_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    UNIQUE KEY uk_email_school (email, school_id),
    INDEX idx_user_role (role),
    INDEX idx_user_school (school_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. STUDENTS (Specialization of Users)
-- ----------------------------------------------------------------------------
-- Maps user accounts to student-specific academic profiles.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    school_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED DEFAULT NULL,
    student_code VARCHAR(50) NOT NULL,
    grade_level VARCHAR(20) NOT NULL,
    section VARCHAR(10) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    gpa_current DECIMAL(4,2) DEFAULT 0.00,
    attendance_rate DECIMAL(5,2) DEFAULT 0.00,
    CONSTRAINT fk_student_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_parent FOREIGN KEY (parent_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uk_student_code_school (student_code, school_id),
    INDEX idx_student_search (school_id, student_code),
    INDEX idx_student_grade (grade_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. TRACER ACTIVITIES (Audit Trail & Timeline Feature)
-- ----------------------------------------------------------------------------
-- Stores all behavioral and academic activities for the Tracer timeline.
-- Includes attendance markers, grade updates, and teacher remarks.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tracer_activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    activity_type ENUM('attendance', 'grade', 'remark', 'achievement', 'alert') NOT NULL,
    severity ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    icon VARCHAR(50) DEFAULT 'info',
    color VARCHAR(20) DEFAULT 'blue',
    reference_id BIGINT UNSIGNED DEFAULT NULL,
    reference_table VARCHAR(50) DEFAULT NULL,
    activity_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tracer_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_tracer_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_tracer_lookup (student_id, activity_date DESC),
    INDEX idx_tracer_school (school_id),
    INDEX idx_tracer_type (activity_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. SEED DATA (CORE SYSTEM DATA)
-- ----------------------------------------------------------------------------

-- 5.1 Insert Schools (The Tenants)
INSERT INTO schools (school_name, primary_color, academic_year) VALUES 
('Ethio-Excellence Academy', '#10B981', '2025-2026'),
('Future Leaders School', '#6366F1', '2025-2026');

-- 5.2 Insert Admins
INSERT INTO users (school_id, first_name, last_name, email, password, role) VALUES 
(1, 'Abebe', 'Bikila', 'admin@ethio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'Sara', 'Tadesse', 'admin@future.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 5.3 Insert Initial Teacher, Parent & Student (Linking Example)
INSERT INTO users (school_id, first_name, last_name, email, password, role) VALUES 
(1, 'Kebede', 'Kassa', 'parent@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent'),
(1, 'Samuel', 'Kebede', 'samuel@ethio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO students (user_id, school_id, parent_id, student_code, grade_level) VALUES 
(4, 1, 3, 'ETH-2025-001', 'Grade 8');

-- 5.4 Initial Tracer Activity
INSERT INTO tracer_activities (school_id, student_id, activity_type, severity, title, description, activity_date) VALUES 
(1, 1, 'achievement', 'success', 'Welcome to the System', 'Student timeline initialized successfully.', CURDATE());

-- ----------------------------------------------------------------------------
-- 6. EXTENDED SEED DATA (DEMO & STRESS TESTING)
-- ----------------------------------------------------------------------------

-- 6.1 Additional Teachers (School 1)
INSERT INTO users (school_id, first_name, last_name, email, password, role) VALUES 
(1, 'Desta', 'Alemayehu', 'desta.teacher@ethio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
(1, 'Mulu', 'Tesfaye', 'mulu.teacher@ethio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher');

-- 6.2 Additional Parents & Students (School 1)
INSERT INTO users (school_id, first_name, last_name, email, password, role) VALUES 
(1, 'Hanna', 'Zeleke', 'hanna.parent@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'parent'),
(1, 'Dawit', 'Samuel', 'dawit.student@ethio.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

INSERT INTO students (user_id, school_id, parent_id, student_code, grade_level) VALUES 
(7, 1, 6, 'ETH-2025-002', 'Grade 5');

-- 6.3 Demo Tracer Activities
INSERT INTO tracer_activities (school_id, student_id, activity_type, severity, title, description, activity_date) VALUES 
(1, 1, 'attendance', 'danger', 'Unexcused Absence', 'Missed Mathematics period 1 without prior notice.', DATE_SUB(CURDATE(), INTERVAL 1 DAY)),
(1, 1, 'grade', 'info', 'Grade Update', 'Mathematics Mid-term: 88/100', DATE_SUB(CURDATE(), INTERVAL 2 DAY)),
(1, 1, 'remark', 'warning', 'Behavioral Note', 'Talkative during quiet study time. Parent notified.', DATE_SUB(CURDATE(), INTERVAL 3 DAY));

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- END OF CONSOLIDATED SQL SCRIPT
-- ============================================================================
