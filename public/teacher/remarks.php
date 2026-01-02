<?php
/**
 * Student Remarks - Teacher Portal
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('teacher');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

$success = '';
$error = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_remark'])) {
    $student_id = $_POST['student_id'];
    $type = $_POST['remark_type'];
    $comment = $_POST['comment'];

    try {
        $stmt = $pdo->prepare("
            INSERT INTO remarks (student_id, teacher_id, remark_type, comment)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $user['id'], $type, $comment]);
        $success = "Remark posted successfully.";
    } catch (PDOException $e) {
        $error = "Error posting remark: " . $e->getMessage();
    }
}

// Fetch Students assigned to this teacher across all their classes
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT s.id, CONCAT(s.first_name, ' ', s.last_name) as full_name, s.student_code, s.photo, s.grade_level, s.section
        FROM students s
        JOIN classes c ON s.grade_level = c.grade_level AND s.section = c.section
        WHERE c.teacher_id = ? AND s.deleted_at IS NULL AND c.deleted_at IS NULL
        ORDER BY s.last_name, s.first_name
    ");
    $stmt->execute([$user['id']]);
    $assigned_students = $stmt->fetchAll();
    
    // Fetch recent remarks by this teacher
    $stmt = $pdo->prepare("
        SELECT r.*, CONCAT(s.first_name, ' ', s.last_name) as student_name, s.student_code
        FROM remarks r
        JOIN students s ON r.student_id = s.id
        WHERE r.teacher_id = ? AND r.deleted_at IS NULL
        ORDER BY r.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $recent_remarks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Remarks Page Error: " . $e->getMessage());
    $assigned_students = $recent_remarks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Behavioral Remarks - <?php echo APP_NAME; ?></title>
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
    
    <?php include __DIR__ . '/../../includes/partials/sidebar_teacher.php'; ?>

    <main class="ml-0 md:ml-[280px] min-h-screen">
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Observation Journal</h1>
            <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <!-- Remark Form -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-[40px] p-8 border border-gray-100 shadow-sm sticky top-28">
                        <h2 class="text-2xl font-black text-gray-900 mb-6">Log New Entry</h2>
                        
                        <?php if ($success): ?>
                            <div class="mb-6 p-4 bg-green-50 text-green-700 font-bold rounded-2xl text-sm">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Select Learner</label>
                                <select name="student_id" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 transition-all outline-none appearance-none">
                                    <option value="">-- Choose Student --</option>
                                    <?php foreach ($assigned_students as $s): ?>
                                        <option value="<?php echo $s['id']; ?>">
                                            <?php echo htmlspecialchars($s['full_name']); ?> (<?php echo $s['grade_level']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Entry Focus</label>
                                <div class="grid grid-cols-3 gap-2">
                                    <?php foreach (['academic', 'behavioral', 'general'] as $t): ?>
                                        <label class="cursor-pointer group">
                                            <input type="radio" name="remark_type" value="<?php echo $t; ?>" <?php echo $t === 'general' ? 'checked' : ''; ?> class="hidden peer">
                                            <div class="text-center py-3 rounded-xl border-2 border-transparent bg-gray-50 text-gray-400 font-bold text-[10px] uppercase tracking-widest peer-checked:bg-[var(--brand-color)] peer-checked:text-white transition-all">
                                                <?php echo $t; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Professional Remark</label>
                                <textarea name="comment" required rows="5" placeholder="Document your observations here..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-semibold text-gray-700 transition-all outline-none resize-none"></textarea>
                            </div>

                            <button type="submit" name="submit_remark" class="w-full bg-[var(--brand-color)] text-white py-5 rounded-[24px] font-black shadow-xl shadow-[var(--brand-color)]/20 hover:scale-[1.02] active:scale-95 transition-all">
                                Broadcast Remark
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Recent Remarks Feed -->
                <div class="lg:col-span-2">
                    <h2 class="text-2xl font-black text-gray-900 mb-8">Journal History</h2>
                    
                    <div class="space-y-6">
                        <?php if (count($recent_remarks) > 0): ?>
                            <?php foreach ($recent_remarks as $remark): ?>
                                <div class="bg-white rounded-[32px] p-8 border border-gray-100 shadow-sm animate-slide-in">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-black text-gray-400">
                                                <?php echo substr($remark['student_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900"><?php echo htmlspecialchars($remark['student_name']); ?></p>
                                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter"><?php echo $remark['student_code']; ?></p>
                                            </div>
                                        </div>
                                        <span class="badge badge-<?php echo $remark['remark_type'] === 'academic' ? 'info' : ($remark['remark_type'] === 'behavioral' ? 'warning' : 'purple'); ?>">
                                            <?php echo ucfirst($remark['remark_type']); ?>
                                        </span>
                                    </div>
                                    <p class="text-gray-600 font-medium leading-relaxed italic">"<?php echo htmlspecialchars($remark['comment']); ?>"</p>
                                    <div class="mt-6 pt-6 border-t border-gray-50 flex items-center justify-between text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                                        <span>Logged by You</span>
                                        <span><?php echo time_elapsed_string($remark['created_at']); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-20 bg-white rounded-[40px] border border-gray-100">
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">No recent observations recorded.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
