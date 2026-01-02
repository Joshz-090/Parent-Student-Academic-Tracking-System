<?php
/**
 * Teacher Dashboard - Premium Edition
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('teacher');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

// Fetch Teacher Stats
try {
    // Total Classes assigned
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE teacher_id = ? AND deleted_at IS NULL");
    $stmt->execute([$user['id']]);
    $total_classes = $stmt->fetchColumn();

    // Total Students across all assigned classes (Automatic Mapping)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT s.id) 
        FROM students s
        JOIN classes c ON s.grade_level = c.grade_level AND s.section = c.section
        WHERE c.teacher_id = ? AND c.deleted_at IS NULL AND s.deleted_at IS NULL
    ");
    $stmt->execute([$user['id']]);
    $total_students = $stmt->fetchColumn();

    // Today's Attendance count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM attendance 
        WHERE recorded_by = ? AND date = CURDATE()
    ");
    $stmt->execute([$user['id']]);
    $today_attendance = $stmt->fetchColumn();

    // Recent Activity (Remarks/Grades)
    $stmt = $pdo->prepare("
        (SELECT 'grade' as type, s.first_name, s.last_name, g.score as value, g.created_at 
         FROM grades g 
         JOIN students s ON g.student_id = s.id 
         WHERE g.recorded_by = ? AND s.deleted_at IS NULL
         ORDER BY g.created_at DESC LIMIT 5)
        UNION ALL
        (SELECT 'remark' as type, s.first_name, s.last_name, r.remark_type as value, r.created_at 
         FROM remarks r 
         JOIN students s ON r.student_id = s.id 
         WHERE r.teacher_id = ? AND r.deleted_at IS NULL AND s.deleted_at IS NULL
         ORDER BY r.created_at DESC LIMIT 5)
        ORDER BY created_at DESC LIMIT 5
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $recent_activities = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Teacher Dashboard Stats Error: " . $e->getMessage());
    $total_classes = $total_students = $today_attendance = 0;
    $recent_activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Hub - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    <style>
        :root {
            --brand-color: <?php echo $branding['primary_color'] ?? '#3B82F6'; ?>;
            --brand-color-rgb: <?php echo hexToRgb($branding['primary_color'] ?? '#3B82F6'); ?>;
        }
    </style>
</head>
<body class="bg-[#f8fafc]">
    
    <!-- Sidebar Overlay -->
    <?php include __DIR__ . '/../../includes/partials/sidebar_teacher.php'; ?>

    <!-- Main Content -->
    <main class="ml-0 md:ml-[280px] min-h-screen">
        <!-- Top Navigation -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Faculty Portal</h1>
            
            <div class="flex items-center gap-6">
                <div class="hidden md:flex flex-col items-end">
                    <span class="text-sm font-bold text-gray-900 leading-none"><?php echo htmlspecialchars($user['name']); ?></span>
                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1"><?php echo date('l, M j, Y'); ?></span>
                </div>
                <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <!-- Hero Section -->
            <div class="mb-10">
                <h2 class="text-3xl font-black text-gray-900 mb-2 italic">Welcome back, Professor.</h2>
                <p class="text-gray-500 font-medium">Monitoring academic progress for <span class="text-[var(--brand-color)] font-bold"><?php echo htmlspecialchars($branding['school_name']); ?></span>.</p>
            </div>

            <!-- Bento Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <!-- Card: Assigned Classes -->
                <div class="bento-card">
                    <span class="card-title text-indigo-500 italic">Load</span>
                    <div class="stat-value text-gray-900"><?php echo $total_classes; ?></div>
                    <p class="text-[10px] font-black text-gray-400 mt-2 uppercase">Scheduled Subjects</p>
                </div>

                <!-- Card: Impact -->
                <div class="bento-card">
                    <span class="card-title text-emerald-500 italic">Impact</span>
                    <div class="stat-value text-gray-900"><?php echo $total_students; ?></div>
                    <p class="text-[10px] font-black text-gray-400 mt-2 uppercase">Unique Learners</p>
                </div>

                <!-- Card: Today -->
                <div class="bento-card">
                    <span class="card-title text-amber-500 italic">Logs</span>
                    <div class="stat-value text-gray-900"><?php echo $today_attendance; ?></div>
                    <p class="text-[10px] font-black text-gray-400 mt-2 uppercase">Records Stored Today</p>
                </div>

                <!-- Card: Quick Access -->
                <div class="bg-[var(--brand-color)] rounded-[32px] p-8 text-white flex flex-col justify-between shadow-lg shadow-[var(--brand-color)]/20">
                    <p class="text-xs font-black uppercase tracking-widest opacity-80 italic">Direct Actions</p>
                    <div class="space-y-3 mt-4">
                        <a href="attendance.php" class="block bg-white/20 hover:bg-white/30 p-2 rounded-xl text-center text-xs font-bold transition-all">Today's Attendance</a>
                        <a href="grades.php" class="block bg-white/20 hover:bg-white/30 p-2 rounded-xl text-center text-xs font-bold transition-all">Submit Scores</a>
                    </div>
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                <!-- Activity Feed -->
                <div class="xl:col-span-2 bg-white rounded-[32px] border border-gray-100 p-8 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h3 class="text-xl font-black text-gray-900">Personal Activity Log</h3>
                            <p class="text-sm text-gray-400 font-bold uppercase tracking-tighter">Your recent submissions</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <?php if (count($recent_activities) > 0): ?>
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="flex items-center gap-6 p-4 rounded-3xl hover:bg-gray-50 transition-colors border border-transparent hover:border-gray-100">
                                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shrink-0 <?php echo $activity['type'] === 'grade' ? 'bg-emerald-100 text-emerald-600' : 'bg-purple-100 text-purple-600'; ?>">
                                        <?php if($activity['type'] === 'grade'): ?>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <?php else: ?>
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-1">
                                            <h4 class="font-bold text-gray-900"><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></h4>
                                            <span class="text-[10px] font-black text-gray-400 uppercase"><?php echo time_elapsed_string($activity['created_at']); ?></span>
                                        </div>
                                        <p class="text-sm text-gray-500 font-medium">
                                            <?php if($activity['type'] === 'grade'): ?>
                                                Finalized score: <span class="text-emerald-600 font-bold"><?php echo $activity['value']; ?></span>
                                            <?php else: ?>
                                                Added <span class="text-purple-600 font-bold"><?php echo $activity['value']; ?></span> remark
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-20">
                                <p class="text-gray-400 font-bold italic">No submissions recorded in the current session.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Calendar/Quick Link -->
                <div class="space-y-8">
                    <div class="bg-gray-900 rounded-[32px] p-8 text-white">
                        <h3 class="font-black text-xl mb-6 italic underline decoration-blue-500 underline-offset-8">Academic Pulse</h3>
                        <div class="space-y-6">
                            <div class="flex justify-between items-center text-sm font-bold opacity-60">
                                <span>Period</span>
                                <span><?php echo $branding['academic_year']; ?></span>
                            </div>
                            <div class="flex justify-between items-center text-sm font-bold">
                                <span>System Status</span>
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                    Operational
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
