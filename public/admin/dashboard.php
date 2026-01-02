<?php
/**
 * Admin Dashboard - Premium Redesign
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

// Fetch dashboard statistics
try {
    // Total students
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM students WHERE school_id = ? AND deleted_at IS NULL");
    $stmt->execute([$school_id]);
    $total_students = $stmt->fetch()['total'];
    
    // Total teachers
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE school_id = ? AND role = 'teacher' AND deleted_at IS NULL");
    $stmt->execute([$school_id]);
    $total_teachers = $stmt->fetch()['total'];
    
    // Total parents
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE school_id = ? AND role = 'parent' AND deleted_at IS NULL");
    $stmt->execute([$school_id]);
    $total_parents = $stmt->fetch()['total'];
    
    // Today's attendance rate
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present
        FROM attendance a
        JOIN students s ON a.student_id = s.id
        WHERE s.school_id = ? AND a.date = CURDATE()
    ");
    $stmt->execute([$school_id]);
    $attendance_data = $stmt->fetch();
    $attendance_rate = $attendance_data['total'] > 0 
        ? round(($attendance_data['present'] / $attendance_data['total']) * 100, 1) 
        : 0;
    
    // Students at risk
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) as at_risk
        FROM students s
        LEFT JOIN v_attendance_summary va ON s.id = va.student_id
        WHERE s.school_id = ? 
        AND s.deleted_at IS NULL
        AND (va.attendance_rate < 75 OR va.attendance_rate IS NULL)
    ");
    $stmt->execute([$school_id]);
    $students_at_risk = $stmt->fetch()['at_risk'];
    
    // Active classes
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM classes WHERE school_id = ? AND deleted_at IS NULL");
    $stmt->execute([$school_id]);
    $active_classes = $stmt->fetch()['total'];
    
    // Recent activities
    $stmt = $pdo->prepare("
        SELECT 
            ta.title,
            ta.description,
            ta.icon,
            ta.color,
            ta.created_at,
            CONCAT(s.first_name, ' ', s.last_name) as student_name
        FROM tracer_activities ta
        JOIN students s ON ta.student_id = s.id
        WHERE s.school_id = ?
        ORDER BY ta.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$school_id]);
    $recent_activities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    $total_students = $total_teachers = $total_parents = $active_classes = $students_at_risk = 0;
    $attendance_rate = 0;
    $recent_activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    <style>
        :root {
            --brand-color: <?php echo $branding['primary_color'] ?? '#3B82F6'; ?>;
            --brand-color-rgb: <?php echo hexToRgb($branding['primary_color'] ?? '#3B82F6'); ?>;
            --brand-text-color: <?php echo getContrastColor($branding['primary_color'] ?? '#3B82F6'); ?>;
        }
    </style>
</head>
<body class="bg-[#f8fafc]">
    
    <?php include __DIR__ . '/../../includes/partials/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-0 md:ml-[280px] min-h-screen">
        <!-- Top Navigation -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Admin Console</h1>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-900 leading-none"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1">Super Admin</span>
                </div>
                <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <!-- Hero Section -->
            <div class="mb-10">
                <h2 class="text-3xl font-black text-gray-900 mb-2">Institutional Analytics</h2>
                <p class="text-gray-500 font-medium">Real-time indicators for <span class="text-[var(--brand-color)] font-bold"><?php echo htmlspecialchars($branding['school_name']); ?></span> academic session.</p>
            </div>

            <!-- Bento Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                <!-- Card: Total Students -->
                <div class="bento-card group">
                    <div class="relative z-10">
                        <span class="card-title text-blue-500">Learners</span>
                        <div class="stat-value text-gray-900"><?php echo $total_students; ?></div>
                        <p class="text-xs font-bold text-gray-400 mt-2 flex items-center gap-1">
                            <svg class="w-3 h-3 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z"></path></svg>
                            Total Enrolled
                        </p>
                    </div>
                    <svg class="card-icon-bg w-24 h-24 text-blue-100" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                </div>

                <!-- Card: Faculty -->
                <div class="bento-card">
                    <div class="relative z-10">
                        <span class="card-title text-emerald-500">Faculty</span>
                        <div class="stat-value text-gray-900"><?php echo $total_teachers; ?></div>
                        <p class="text-xs font-bold text-gray-400 mt-2 uppercase tracking-tighter">Active Educators</p>
                    </div>
                    <svg class="card-icon-bg w-24 h-24 text-emerald-100" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12V12a1 1 0 002 0v-1.28l.33.141 5.91 2.532a1 1 0 01.31.232l5.7 1.8a1 1 0 010 1.9l-5.7 1.8a1 1 0 01-.31.232l-5.91 2.532A1 1 0 016.32 20l-5.7-1.8a1 1 0 010-1.9l5.7-1.8a1 1 0 01.31-.232l.33-.141V14.28l1.69-.723V12.12l1.69-.723z"></path></svg>
                </div>

                <!-- Card: Attendance -->
                <div class="bento-card">
                    <div class="relative z-10">
                        <span class="card-title text-amber-500">Attendance</span>
                        <div class="stat-value text-gray-900"><?php echo $attendance_rate; ?><span class="text-lg ml-1">%</span></div>
                        <p class="text-xs font-bold text-gray-400 mt-2">Current Daily average</p>
                    </div>
                    <div class="absolute right-6 top-1/2 -translate-y-1/2 w-16 h-16 rounded-full border-8 border-amber-50 border-t-amber-500 animate-spin-slow"></div>
                </div>

                <!-- Card: At Risk -->
                <div class="bento-card border-red-100 bg-red-50/20">
                    <div class="relative z-10">
                        <span class="card-title text-red-600">Priority Monitor</span>
                        <div class="stat-value text-red-600"><?php echo $students_at_risk; ?></div>
                        <p class="text-xs font-bold text-red-400 mt-2 uppercase">Needs Intervention</p>
                    </div>
                </div>

                <!-- Card: Classes -->
                <div class="bento-card col-span-1 lg:col-span-2 flex items-center justify-between">
                    <div>
                        <span class="card-title text-indigo-500">Curriculum</span>
                        <div class="stat-value text-gray-900"><?php echo $active_classes; ?></div>
                        <p class="text-xs font-bold text-gray-400 mt-2">Scheduled Sessions</p>
                    </div>
                    <div class="hidden sm:flex items-center gap-2">
                        <?php for($i=0; $i<5; $i++): ?>
                            <div class="w-8 h-12 bg-indigo-50 rounded-lg flex items-end p-1">
                                <div class="w-full bg-indigo-400 rounded-sm" style="height: <?php echo rand(20, 80); ?>%"></div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Recent Activity & Calendar -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Activity Feed -->
                <div class="xl:col-span-2 bg-white rounded-[32px] border border-gray-100 p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <h3 class="text-xl font-black text-gray-900">System Logs</h3>
                        <button class="text-xs font-bold text-[var(--brand-color)] uppercase tracking-widest hover:underline">View Journal</button>
                    </div>
                    
                    <div class="space-y-6">
                        <?php if (count($recent_activities) > 0): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="flex items-start gap-4 p-5 hover:bg-gray-50 rounded-2xl transition-colors border border-transparent hover:border-gray-100 group">
                                    <div class="w-12 h-12 rounded-xl bg-opacity-10 <?php 
                                        echo $activity['color'] === 'red' ? 'bg-red-500 text-red-600' : 
                                             ($activity['color'] === 'green' ? 'bg-green-500 text-green-600' : 
                                             ($activity['color'] === 'yellow' ? 'bg-amber-500 text-amber-600' : 'bg-blue-500 text-blue-600')); 
                                    ?> flex items-center justify-center shrink-0">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="text-sm font-black text-gray-900"><?php echo htmlspecialchars($activity['student_name']); ?></span>
                                            <span class="text-[10px] font-bold text-gray-400"><?php echo date('h:i A', strtotime($activity['created_at'])); ?></span>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-600 truncate"><?php echo htmlspecialchars($activity['title']); ?></p>
                                        <p class="text-xs text-gray-400 mt-1 line-clamp-1 italic"><?php echo htmlspecialchars($activity['description']); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-20">
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No recent log entries</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Snapshot Panel -->
                <div class="bg-[var(--brand-color)] rounded-[32px] p-8 text-[var(--brand-text-color)] shadow-xl flex flex-col justify-between overflow-hidden relative transition-colors duration-300">
                    <div class="relative z-10">
                        <h3 class="text-2xl font-black mb-6 leading-tight">Academic Year<br/><?php echo htmlspecialchars($branding['academic_year']); ?></h3>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full bg-black/10 flex items-center justify-center font-bold">Q1</div>
                                <div>
                                    <p class="text-xs font-bold opacity-60 uppercase">Current Quarter</p>
                                    <p class="text-lg font-black italic">Prelims</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-20 relative z-10">
                        <p class="opacity-60 text-xs font-black uppercase tracking-widest mb-4">Quick Link</p>
                        <a href="school_settings.php" class="inline-flex items-center gap-2 bg-[var(--brand-text-color)] text-[var(--brand-color)] px-6 py-3 rounded-2xl font-black text-sm hover:scale-105 transition-transform shadow-lg">
                            Configure Boarding
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4-4m4 4H3"></path></svg>
                        </a>
                    </div>

                    <!-- Decorative elements -->
                    <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Optional: Add hover sound effects or micro-interactions
        });
    </script>
</body>
</html>
