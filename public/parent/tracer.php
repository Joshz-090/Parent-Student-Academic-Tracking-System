<?php
/**
 * Student Tracer Timeline - Premium Edition
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('parent');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

// Get student(s) for this parent
try {
    $stmt = $pdo->prepare("
        SELECT id, CONCAT(first_name, ' ', last_name) as full_name, student_code, photo
        FROM students 
        WHERE parent_id = ? AND school_id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$user['id'], $school_id]);
    $students = $stmt->fetchAll();
    
    // Get selected student or default to first
    $selected_student_id = $_GET['student_id'] ?? ($students[0]['id'] ?? null);
    
    if ($selected_student_id) {
        $filter = $_GET['filter'] ?? 'all';
        $sql = "
            SELECT ta.*, DATE_FORMAT(ta.activity_date, '%M %d, %Y') as formatted_date
            FROM tracer_activities ta
            WHERE ta.student_id = ?
        ";
        $params = [$selected_student_id];
        if ($filter !== 'all') {
            $sql .= " AND ta.activity_type = ?";
            $params[] = $filter;
        }
        $sql .= " ORDER BY ta.activity_date DESC, ta.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $activities = $stmt->fetchAll();
        
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$selected_student_id]);
        $student = $stmt->fetch();
    }
} catch (PDOException $e) {
    error_log("Tracer Error: " . $e->getMessage());
    $students = $activities = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracer Timeline - <?php echo APP_NAME; ?></title>
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
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Educational Journey</h1>
            <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-8 max-w-5xl mx-auto">
            <?php if (count($students) === 0): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-8 rounded-[32px] shadow-sm">
                    <p class="font-bold">No registered students linked to your account.</p>
                </div>
            <?php else: ?>
                <!-- Student Header -->
                <div class="mb-10 bg-white rounded-[40px] p-8 border border-gray-100 shadow-sm flex flex-col md:flex-row items-center gap-8">
                    <div class="w-24 h-24 rounded-[32px] bg-gray-100 overflow-hidden border-4 border-white shadow-lg shrink-0">
                        <?php if ($student['photo']): ?>
                            <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $student['photo']; ?>" alt="Photo" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gray-50 text-3xl font-black text-gray-300"><?php echo substr($student['first_name'], 0, 1); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 text-center md:text-left">
                        <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4">
                            <h2 class="text-3xl font-black text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                            <div class="flex justify-center md:justify-start gap-2">
                                <span class="badge badge-info italic"><?php echo htmlspecialchars($student['student_code']); ?></span>
                                <span class="badge badge-purple italic"><?php echo htmlspecialchars($student['grade_level']); ?></span>
                            </div>
                        </div>
                        <div class="flex flex-wrap justify-center md:justify-start gap-3">
                            <a href="?student_id=<?php echo $selected_student_id; ?>&filter=all" class="badge <?php echo $filter === 'all' ? 'bg-[var(--brand-color)] text-[var(--brand-text-color)] shadow-lg shadow-[var(--brand-color)]/20' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?> transition-all px-4 py-2">All Streams</a>
                            <a href="?student_id=<?php echo $selected_student_id; ?>&filter=attendance" class="badge <?php echo $filter === 'attendance' ? 'bg-[var(--brand-color)] text-[var(--brand-text-color)]' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?> transition-all px-4 py-2">Attendance</a>
                            <a href="?student_id=<?php echo $selected_student_id; ?>&filter=grade" class="badge <?php echo $filter === 'grade' ? 'bg-[var(--brand-color)] text-[var(--brand-text-color)]' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?> transition-all px-4 py-2">Grades</a>
                            <a href="?student_id=<?php echo $selected_student_id; ?>&filter=remark" class="badge <?php echo $filter === 'remark' ? 'bg-[var(--brand-color)] text-[var(--brand-text-color)]' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'; ?> transition-all px-4 py-2">Teacher Remarks</a>
                        </div>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="timeline">
                    <?php if (count($activities) > 0): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="timeline-item animate-slide-in">
                                <div class="timeline-icon <?php echo htmlspecialchars($activity['color']); ?>">
                                    <?php
                                    $icons = ['x-circle' => '✕', 'clock' => '🕒', 'check-circle' => '✓', 'star' => '⭐', 'trending-up' => '📈', 'message-square' => '💬'];
                                    echo $icons[$activity['icon']] ?? '•';
                                    ?>
                                </div>
                                <div class="timeline-content">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-lg font-black text-gray-900"><?php echo htmlspecialchars($activity['title']); ?></h4>
                                        <span class="text-[10px] font-black text-gray-400 border border-gray-100 px-3 py-1 rounded-full uppercase tracking-widest"><?php echo $activity['formatted_date']; ?></span>
                                    </div>
                                    <p class="text-gray-500 font-medium leading-relaxed mb-4">
                                        <?php echo htmlspecialchars($activity['description'] ?? ''); ?>
                                        <?php if ($activity['activity_type'] === 'remark'): ?>
                                            <span class="block text-[10px] text-gray-300 uppercase font-black mt-2 italic">— Official Faculty Correspondence</span>
                                        <?php endif; ?>
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <span class="badge badge-<?php echo $activity['color'] === 'red' ? 'danger' : ($activity['color'] === 'green' ? 'success' : ($activity['color'] === 'yellow' ? 'warning' : 'info')); ?>">
                                            <?php echo ucfirst($activity['activity_type']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-20 bg-white rounded-[40px] border border-gray-100">
                            <p class="text-gray-400 font-bold italic">The timeline is silent. No events recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
