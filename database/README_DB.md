# Database Documentation: PS-Tracer System

This document provides an overview of the database structure for the **Parent-Student Academic Tracking System (PS-tracer)**.

## 📁 File Information
- **Primary Script**: `ps_tracer_system.sql`
- **Engine**: InnoDB
- **Charset**: utf8mb4_unicode_ci

## 🏗️ Schema Overview

The database is designed with a **Multi-School Isolation** strategy, ensuring data privacy across different institutions.

### Core Entities
1.  **`schools`**: The root of the system. Stores individual school branding (name, logo, colors) and academic year settings.
2.  **`users`**: Implements Role-Based Access Control (RBAC). Roles include `admin`, `teacher`, `parent`, and `student`.
3.  **`students`**: Academic profiles linked to users and parents.
4.  **`tracer_activities`**: The central log for the "Tracer" timeline feature. Stores attendance, grades, and remarks.

## 🚀 Setup Instructions

1.  **Create Database**:
    ```sql
    CREATE DATABASE ps_tracer;
    USE ps_tracer;
    ```
2.  **Import Script**:
    Import the `ps_tracer_system.sql` file using your preferred tool (phpMyAdmin, MySQL Workbench, or CLI).
    ```bash
    mysql -u [username] -p ps_tracer < ps_tracer_system.sql
    ```

## 👥 Group Assignment Details
- **Project**: Parent-Student Academic Tracking System
- **Developer**: Joshz-090
- **Submission**: Web Development Assignment (DB Part)

---
> [!NOTE]
> The script includes demo seed data for both "Ethio-Excellence Academy" and "Future Leaders School" to demonstrate multi-school isolation.
