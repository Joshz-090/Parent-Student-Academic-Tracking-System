<?php
/**
 * Student Tracer Timeline - Refactored OO Edition
 */

require_once __DIR__ . '/../../src/Config/config.php';
require_once __DIR__ . '/../../autoload.php';

use App\Core\Database;
use App\Core\SessionManager;
use App\Auth\AuthMiddleware;
use App\Models\School;

$session = new SessionManager();
$auth = new AuthMiddleware($session);
$auth->requireRole('parent');

$user = $auth->getCurrentUser();
$schoolId = $user['school_id'];

// Get School Branding via Model
$db = new Database($schoolId);
$stmt = $db->secureQuery("SELECT * FROM schools WHERE id = :id", ['id' => $schoolId]);
$schoolData = $stmt->fetch();
$school = new School($schoolData);

// Fetch students linked to parent (simplified for view initialization)
$sql = "SELECT id, first_name, last_name, student_code, photo, grade_level 
        FROM students 
        WHERE parent_id = :parent_id AND deleted_at IS NULL";
$stmt = $db->secureQuery($sql, ['parent_id' => $user['id']]);
$students = $stmt->fetchAll();

$selectedStudentId = $_GET['student_id'] ?? ($students[0]['id'] ?? null);
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
            --brand-color: <?php echo $school->primaryColor; ?>;
            --brand-color-rgb: <?php echo \App\Core\ViewHelper::hexToRgb($school->primaryColor); ?>;
            --brand-text-color: <?php echo \App\Core\ViewHelper::getContrastColor($school->primaryColor); ?>;
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
            <?php if (empty($students)): ?>
                <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-8 rounded-[32px] shadow-sm">
                    <p class="font-bold">No registered students linked to your account.</p>
                </div>
            <?php else: ?>
                <!-- Student Selection -->
                <div class="mb-10 flex flex-wrap gap-4 bg-white p-6 rounded-[32px] border border-gray-100 shadow-sm">
                    <?php foreach ($students as $stu): ?>
                        <button 
                            onclick="loadStudentTimeline(<?php echo $stu['id']; ?>)"
                            class="flex items-center gap-3 px-6 py-3 rounded-2xl transition-all <?php echo $selectedStudentId == $stu['id'] ? 'bg-[var(--brand-color)] text-[var(--brand-text-color)] shadow-lg' : 'bg-gray-50 text-gray-600 hover:bg-gray-100'; ?>">
                            <div class="w-8 h-8 rounded-full bg-white/20 flex items-center justify-center font-black">
                                <?php echo substr($stu['first_name'], 0, 1); ?>
                            </div>
                            <span class="font-bold"><?php echo htmlspecialchars($stu['first_name']); ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <!-- Timeline Container (Now AJAX driven) -->
                <div id="tracer-timeline" class="space-y-6">
                    <!-- Activities will be rendered here by tracer.js -->
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/tracer.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const defaultId = <?php echo json_encode($selectedStudentId); ?>;
            if (defaultId) {
                fetchTracerTimeline(defaultId);
            }
        });

        function loadStudentTimeline(id) {
            // Update UI buttons
            document.querySelectorAll('button[onclick^="loadStudentTimeline"]').forEach(btn => {
                btn.classList.remove('bg-[var(--brand-color)]', 'text-[var(--brand-text-color)]', 'shadow-lg');
                btn.classList.add('bg-gray-50', 'text-gray-600');
            });
            event.currentTarget.classList.add('bg-[var(--brand-color)]', 'text-[var(--brand-text-color)]', 'shadow-lg');
            event.currentTarget.classList.remove('bg-gray-50', 'text-gray-600');
            
            fetchTracerTimeline(id);
        }
    </script>
</body>
</html>
