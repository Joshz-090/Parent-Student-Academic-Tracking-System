# Multi-School Parent–Student Tracer System
 
A professional, scalable Parent–Student Tracer System built with PHP 8.0+, MySQL, Vanilla JavaScript, and Tailwind CSS. Designed to support multiple schools with individual branding, secure role-based access control, and a unique "Tracer" feature for tracking student progress.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-orange.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## 🌟 Key Features

### Multi-School Support
- **School Isolation**: Each school operates independently with its own data
- **Custom Branding**: Schools can set their own name, logo, and primary color
- **Scalable Architecture**: Add unlimited schools to the same system

### Role-Based Access Control (RBAC)
- **Admin**: Full system control for managing users, students, classes, and school settings
- **Teacher**: Record attendance, input grades, and write remarks (placeholder implemented)
- **Parent/Student**: View-only access to attendance, grades, remarks, and the Tracer timeline

### 🎯 Tracer Feature (Product Value)
The **Tracer** is a vertical timeline showing a student's progress story with auto-generated activities:
- ✅ "Absent on Monday"
- ⏰ "Arrived late to class"
- ⭐ "Excellent! Scored 95 in Mathematics"
- 📈 "Attendance improved this week"
- 💬 "Teacher remark: Shows great problem-solving skills"

Activities are ordered by most recent and can be filtered by type (attendance, grades, remarks).

### Security
- ✅ PDO prepared statements for all SQL queries
- ✅ Password hashing with `password_hash()` and `password_verify()`
- ✅ Session-based authentication with timeout
- ✅ CSRF protection
- ✅ Role-based route protection
- ✅ Multi-school data isolation

### Professional UI/UX
- 📱 Mobile-first responsive design
- 🎨 Tailwind CSS for modern styling
- 🎯 Bento-box grid layout for admin dashboard
- 🔔 Toast notifications for user feedback
- 🎨 School-brandable interface (logo + colors)

## 📋 System Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache/Nginx with mod_rewrite
- **Extensions**: PDO, PDO_MySQL, GD (for image uploads)

## 🚀 Installation

### Step 1: Clone or Download

```bash
# Place the project in your web server directory
# For XAMPP: C:\xampp\htdocs\PS-tracer
# For WAMP: C:\wamp64\www\PS-tracer
```

### Step 2: Database Setup

1. Start your MySQL server (via XAMPP/WAMP control panel)

2. Import the database schema:

```bash
# Using MySQL command line
mysql -u root -p < database/schema.sql

# Or use phpMyAdmin:
# - Open http://localhost/phpmyadmin
# - Create database 'ps_tracer' (or it will be created automatically)
# - Import database/schema.sql
```

### Step 3: Configure Database Connection

The system uses `.example.php` files to keep your actual credentials secure.

1.  Copy `config/db_connection.example.php` to `config/db_connection.php`
2.  Copy `config/config.example.php` to `config/config.php`
3.  Edit `config/db_connection.php` with your local database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ps_tracer');
define('DB_USER', 'root');
define('DB_PASS', ''); // Default for XAMPP is empty
```

> [!IMPORTANT]
> Never commit `db_connection.php` or `config.php` to version control. They are automatically ignored by `.gitignore`.

### Step 4: Set Permissions

Ensure the uploads directory is writable by the web server:

```bash
# For Linux/macOS
chmod -R 755 public/assets/uploads/
```

### Step 5: Access the System

Open your browser and navigate to:
```
http://localhost/PS-tracer/public/
```

## 🔐 Default Credentials

The system comes with pre-configured demo accounts for testing:

| Role | Email | Password |
|------|-------|----------|
| **Admin** | admin@demo.school | password |
| **Teacher** | teacher1@demo.school | password |
| **Parent** | parent1@demo.school | password |

> [!WARNING]
> **Production Security**: Immediately change these passwords after installation. For production, consider moving database credentials to environment variables.

## 📁 Project Structure

```
PS-tracer/
├── config/
│   ├── db_connection.php      # PDO database connection
│   └── config.php              # Global configuration
├── database/
│   └── schema.sql              # Complete database schema
├── includes/
│   ├── auth.php                # Authentication functions
│   ├── functions.php           # Helper functions
│   ├── session.php             # Session management
│   └── partials/               # Reusable sidebar components
├── middleware/
│   └── auth_middleware.php     # Route protection
├── public/
│   ├── assets/
│   │   ├── css/
│   │   │   └── custom.css      # Custom styles
│   │   ├── js/
│   │   │   ├── toast.js        # Toast notifications
│   │   │   └── main.js         # Global JavaScript
│   │   └── uploads/            # School logos and files
│   ├── admin/
│   │   ├── dashboard.php       # Admin dashboard
│   │   └── school_settings.php # School branding
│   ├── teacher/
│   │   └── dashboard.php       # Teacher portal
│   ├── parent/
│   │   ├── dashboard.php       # Parent dashboard
│   │   └── tracer.php          # Tracer timeline
│   ├── login.php
│   ├── logout.php
│   └── index.php
└── README.md
```

## 🎓 User Guide

### For Administrators

1. **Login** with admin credentials
2. **School Settings**: 
   - Navigate to "School Settings"
   - Update school name, upload logo, set brand color
   - Set academic year and footer text
3. **Manage Users**: Add teachers and parents (feature to be expanded)
4. **Manage Students**: Add students and assign to parents (feature to be expanded)
5. **View Dashboard**: Monitor statistics and recent activities

### For Teachers

1. **Login** with teacher credentials
2. **Record Attendance**: Mark students as present, absent, late, or excused
3. **Input Grades**: Enter quarter scores for assigned subjects
4. **Write Remarks**: Add academic or behavioral comments
5. All actions automatically generate Tracer activities

> 📝 Note: Full teacher features (attendance, grades, remarks pages) are placeholders and can be expanded.

### For Parents/Students

1. **Login** with parent credentials
2. **View Tracer Timeline**: See complete student progress story
3. **Filter Activities**: View specific types (attendance, grades, remarks)
4. **Track Progress**: Monitor attendance rates and grade trends

## 🗄️ Database Schema

### Core Tables
- `schools` - Multi-school support with branding
- `users` - Admin, Teacher, Parent accounts
- `students` - Student profiles
- `classes` - Subject/class management
- `student_classes` - Student-class enrollment

### Academic Tables
- `attendance` - Daily attendance records
- `grades` - Quarter and final grades
- `remarks` - Teacher comments

### Tracer Feature
- `tracer_activities` - Auto-generated timeline events

### Auto-Generation
The system uses **MySQL triggers** to automatically create tracer activities when:
- Attendance is recorded (absent, late, excused)
- Grades are entered (with performance-based messages)
- Remarks are added by teachers

## 🔧 Customization

### Adding a New School

1. Insert into `schools` table:
```sql
INSERT INTO schools (school_name, logo, primary_color, academic_year) 
VALUES ('Your School Name', 'logo.png', '#3B82F6', '2024-2025');
```

2. Create an admin user for that school:
```sql
INSERT INTO users (school_id, name, email, password, role) 
VALUES (2, 'Admin Name', 'admin@yourschool.com', '$2y$10$...', 'admin');
```

### Changing Brand Colors

Admins can change colors via School Settings, or directly in database:
```sql
UPDATE schools SET primary_color = '#FF5733' WHERE id = 1;
```

## 🛡️ Security Best Practices

1. **Change Default Passwords**: Update all demo account passwords
2. **Use HTTPS**: Enable SSL in production
3. **Restrict File Uploads**: Only allow necessary file types
4. **Regular Backups**: Backup database regularly
5. **Update PHP**: Keep PHP and MySQL updated
6. **Environment Variables**: Move credentials to `.env` file in production

## 🐛 Troubleshooting

### Database Connection Error
- Verify MySQL is running
- Check credentials in `config/db_connection.php`
- Ensure `ps_tracer` database exists

### File Upload Fails
- Check `public/assets/uploads/` permissions (755)
- Verify `upload_max_filesize` in `php.ini`

### Session Timeout Issues
- Adjust `SESSION_LIFETIME` in `config/config.php`
- Check PHP session settings

## 📝 Development Roadmap

### Completed ✅
- [x] Multi-school architecture
- [x] Authentication & RBAC
- [x] Admin dashboard with bento-box layout
- [x] School settings & branding
- [x] Tracer timeline feature
- [x] Database schema with triggers
- [x] Responsive UI with Tailwind CSS
- [x] Full teacher portal (attendance, grades, remarks pages)

### To Be Implemented 🚧

- [ ] User management CRUD (teachers, parents)
- [ ] Student management CRUD
- [ ] Class management CRUD
- [ ] Reports and analytics
- [ ] Email notifications
- [ ] Export to PDF/Excel
- [ ] Advanced filtering and search

## 📄 License

This project is open-source and available under the MIT License.

## 👨‍💻 Author

Built as a professional school management system suitable for:
- School projects
- Commercial deployment
- Multi-school SaaS platform

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

## 📧 Support

For issues or questions:
- Check the troubleshooting section
- Review the code comments
- Contact your system administrator

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Status**: Production Ready (Core Features)

Made with ❤️ for educational excellence
