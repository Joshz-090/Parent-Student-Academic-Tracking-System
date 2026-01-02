<?php
/**
 * Parent Dashboard - Student Overview
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('parent');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

$selected_student_id = $_GET['student_id'] ?? null;
$selected_class_id = $_GET['class_id'] ?? null;

// Fetch Parent's Students
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM tracer_activities WHERE student_id = s.id AND activity_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as recent_activities,
               (SELECT attendance_rate FROM v_attendance_summary WHERE student_id = s.id) as attendance_rate
        FROM students s
        WHERE s.parent_id = ? AND s.school_id = ? AND s.deleted_at IS NULL
    ");
    $stmt->execute([$user['id'], $school_id]);
    $students = $stmt->fetchAll();

    $student_classes = [];
    $attendance = [];
    $grades = [];

    if ($selected_student_id) {
        // Fetch classes for this student
        $stmt = $pdo->prepare("
            SELECT c.* 
            FROM classes c
            JOIN student_classes sc ON c.id = sc.class_id
            WHERE sc.student_id = ? AND c.deleted_at IS NULL
        ");
        $stmt->execute([$selected_student_id]);
        $student_classes = $stmt->fetchAll();

        // Fetch attendance for selected class
        if ($selected_class_id) {
            $stmt = $pdo->prepare("SELECT * FROM attendance WHERE student_id = ? AND (class_id = ? OR class_id IS NULL) ORDER BY date DESC LIMIT 10");
            $stmt->execute([$selected_student_id, $selected_class_id]);
            $attendance = $stmt->fetchAll();

            $stmt = $pdo->prepare("SELECT * FROM grades WHERE student_id = ? AND class_id = ? ORDER BY quarter DESC");
            $stmt->execute([$selected_student_id, $selected_class_id]);
            $grades = $stmt->fetchAll();
        }
    }

    // Fetch Teacher Remarks/Messages for priority display
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as teacher_name, s.first_name as student_name
        FROM remarks r
        JOIN users u ON r.teacher_id = u.id
        JOIN students s ON r.student_id = s.id
        WHERE s.parent_id = ? AND r.deleted_at IS NULL
        ORDER BY r.created_at DESC LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Parent Dashboard Error: " . $e->getMessage());
    $students = $messages = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Portal - <?php echo APP_NAME; ?></title>
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
    
    <?php include __DIR__ . '/../../includes/partials/sidebar_parent.php'; ?>

    <main class="ml-0 md:ml-[280px] min-h-screen">
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Guardian Console</h1>
            <div class="flex items-center gap-4">
                <span class="text-sm font-black text-gray-900"><?php echo htmlspecialchars($user['name']); ?></span>
                <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <!-- Priority Messages Section -->
            <?php if (count($messages) > 0): ?>
                <div class="mb-12">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-1.5 h-6 bg-[var(--brand-color)] rounded-full"></div>
                        <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Instant Notifications</h3>
                    </div>
                    <div class="space-y-4">
                        <?php foreach ($messages as $msg): ?>
                            <div class="bg-white border-l-4 border-[var(--brand-color)] p-6 rounded-3xl shadow-sm flex items-start gap-4 animate-slide-in">
                                <div class="w-12 h-12 bg-[var(--brand-color)]/10 rounded-2xl flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6 text-[var(--brand-color)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="font-black text-gray-900"><?php echo htmlspecialchars($msg['teacher_name']); ?> <span class="text-xs font-bold text-gray-400 uppercase ml-2">to <?php echo htmlspecialchars($msg['student_name']); ?></span></p>
                                        <span class="text-[10px] font-bold text-gray-400"><?php echo time_elapsed_string($msg['created_at']); ?></span>
                                    </div>
                                    <p class="text-sm font-semibold text-gray-600 leading-relaxed"><?php echo htmlspecialchars($msg['comment']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Student Selection & Data Display -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Student List -->
                <div class="lg:col-span-1 space-y-4">
                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-6">Select Learner</h3>
                    <?php foreach ($students as $s): ?>
                        <a href="?student_id=<?php echo $s['id']; ?>" class="block bg-white p-6 rounded-[32px] border-2 <?php echo $selected_student_id == $s['id'] ? 'border-[var(--brand-color)] shadow-xl' : 'border-transparent shadow-sm'; ?> transition-all hover:-translate-y-1">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-2xl bg-gray-50 flex items-center justify-center font-black text-gray-300 text-xl border border-gray-100">
                                    <?php echo substr($s['first_name'], 0, 1); ?>
                                </div>
                                <div>
                                    <p class="font-black text-gray-900"><?php echo htmlspecialchars($s['first_name']); ?></p>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($s['student_code']); ?></p>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>

                <!-- Course & Data -->
                <div class="lg:col-span-2">
                    <?php if ($selected_student_id): ?>
                        <div class="bg-white rounded-[40px] p-10 shadow-sm border border-gray-100 h-full">
                            <div class="mb-10 text-center">
                                <h3 class="text-2xl font-black text-gray-900 mb-6 uppercase tracking-tight">Academic Statistics</h3>
                                
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4 px-1">Choose Course / Subject</label>
                                <select onchange="window.location.href='?student_id=<?php echo $selected_student_id; ?>&class_id=' + this.value" class="w-full max-w-sm bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all appearance-none text-center">
                                    <option value="">-- Select a subject --</option>
                                    <?php foreach ($student_classes as $c): ?>
                                        <option value="<?php echo $c['id']; ?>" <?php echo $selected_class_id == $c['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['subject_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if ($selected_class_id): ?>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <!-- Marks Section -->
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between px-2">
                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Grades & Scores</h4>
                                            <span class="badge badge-purple text-[9px] uppercase italic">Quarterly</span>
                                        </div>
                                        <div class="space-y-3">
                                            <?php if (count($grades) > 0): ?>
                                                <?php foreach ($grades as $g): ?>
                                                    <div class="bg-gray-50 p-6 rounded-3xl flex items-center justify-between border border-transparent hover:border-gray-200 transition-all">
                                                        <div>
                                                            <p class="text-xs font-black text-gray-400 uppercase mb-1"><?php echo $g['quarter']; ?></p>
                                                            <p class="font-black text-gray-900">Score Achieved</p>
                                                        </div>
                                                        <div class="text-right">
                                                            <p class="text-2xl font-black text-[var(--brand-color)]"><?php echo $g['score']; ?><span class="text-sm font-bold ml-1">%</span></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-center text-sm font-bold text-gray-400 py-10 italic">No score entries recorded yet.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Attendance Section -->
                                    <div class="space-y-6">
                                        <div class="flex items-center justify-between px-2">
                                            <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest">Session Presence</h4>
                                            <span class="badge badge-info text-[9px] uppercase italic">Recent 10</span>
                                        </div>
                                        <div class="space-y-3">
                                            <?php if (count($attendance) > 0): ?>
                                                <?php foreach ($attendance as $att): ?>
                                                    <div class="bg-gray-50 p-6 rounded-3xl flex items-center justify-between">
                                                        <div>
                                                            <p class="text-[10px] font-black text-gray-400 uppercase mb-1"><?php echo formatDate($att['date']); ?></p>
                                                            <p class="font-black text-gray-900">Class Session</p>
                                                        </div>
                                                        <span class="badge <?php echo $att['status'] === 'present' ? 'badge-info' : 'badge-warning'; ?> uppercase text-[9px] italic"><?php echo $att['status']; ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <p class="text-center text-sm font-bold text-gray-400 py-10 italic">No attendance records found.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="flex flex-col items-center justify-center py-20 text-center opacity-40">
                                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-6">
                                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 8h4m-2 2v-4"></path></svg>
                                    </div>
                                    <p class="text-xl font-black text-gray-900 mb-2">Subject Terminal Ready</p>
                                    <p class="text-sm font-bold text-gray-400 uppercase tracking-widest">Select a course to view real-time indicators</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="bg-gray-50 rounded-[40px] border-4 border-dashed border-gray-200 flex flex-col items-center justify-center p-20 text-center h-full">
                            <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center shadow-sm mb-6">
                                <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 mb-2">Household Overview</h3>
                            <p class="text-gray-400 font-bold max-w-sm leading-relaxed">Please select one of your students from the left sidebar to access their specific academic performance and attendance tracers.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
