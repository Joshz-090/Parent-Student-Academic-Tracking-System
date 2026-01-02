<?php
/**
 * Grade Management - Teacher Portal
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

$selected_quarter = $_GET['quarter'] ?? 'Q1';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_grades'])) {
    $class_id = $_POST['class_id'];
    $quarter = $_POST['quarter'];
    $scores = $_POST['score'] ?? []; // student_id => score

    try {
        $pdo->beginTransaction();
        
        foreach ($scores as $student_id => $score) {
            if ($score === '') continue; // Skip empty
            
            $score = (float)$score;
            $grade_letter = getGradeLetter($score);
            
            $stmt = $pdo->prepare("
                INSERT INTO grades (student_id, class_id, quarter, score, grade_letter, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                score = VALUES(score), 
                grade_letter = VALUES(grade_letter),
                recorded_by = VALUES(recorded_by)
            ");
            $stmt->execute([$student_id, $class_id, $quarter, $score, $grade_letter, $user['id']]);
        }
        
        $pdo->commit();
        $success = "Grades for $quarter updated successfully.";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error updating grades: " . $e->getMessage();
    }
}

// Fetch Teacher's Classes
try {
    $stmt = $pdo->prepare("
        SELECT id, subject_name, grade_level, section 
        FROM classes 
        WHERE teacher_id = ? AND school_id = ? AND deleted_at IS NULL
    ");
    $stmt->execute([$user['id'], $school_id]);
    $classes = $stmt->fetchAll();
    
    $selected_class_id = $_GET['class_id'] ?? null;
    $students = [];
    
    if ($selected_class_id) {
        $stmt = $pdo->prepare("
            SELECT s.id, s.first_name, s.last_name, s.student_code, s.photo
            FROM students s
            JOIN classes c ON s.grade_level = c.grade_level AND s.section = c.section
            WHERE c.id = ? AND s.deleted_at IS NULL AND c.deleted_at IS NULL
            ORDER BY s.last_name, s.first_name
        ");
        $stmt->execute([$selected_class_id]);
        $students = $stmt->fetchAll();
        
        // Get existing grades
        $stmt = $pdo->prepare("SELECT student_id, score, grade_letter FROM grades WHERE class_id = ? AND quarter = ?");
        $stmt->execute([$selected_class_id, $selected_quarter]);
        $existing_grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $marked_data = [];
        foreach ($existing_grades as $row) {
            $marked_data[$row['student_id']] = $row;
        }
    }
} catch (PDOException $e) {
    error_log("Grades Page Error: " . $e->getMessage());
    $classes = $students = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Management - <?php echo APP_NAME; ?></title>
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
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Academic Records</h1>
            <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <?php if ($success): ?>
                <div class="mb-6 p-5 bg-green-50 border-l-4 border-green-500 text-green-700 font-bold rounded-r-2xl shadow-sm">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="mb-10 flex flex-wrap gap-6 items-end bg-white p-8 rounded-[40px] shadow-sm border border-gray-100">
                <div class="flex-1 min-w-[250px]">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Target Subject</label>
                    <div class="relative">
                        <select onchange="window.location.href='?class_id='+this.value+'&quarter=<?php echo $selected_quarter; ?>'" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 appearance-none transition-all outline-none">
                            <option value="">-- Choose Subject --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class['id'] == $selected_class_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['subject_name']); ?> (<?php echo htmlspecialchars($class['grade_level']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
                
                <div class="w-full sm:w-auto">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Academic Quarter</label>
                    <div class="flex bg-gray-50 p-1 rounded-2xl">
                        <?php foreach (['Q1', 'Q2', 'Q3', 'Q4'] as $q): ?>
                            <a href="?class_id=<?php echo $selected_class_id; ?>&quarter=<?php echo $q; ?>" class="px-6 py-3 rounded-xl font-bold text-sm transition-all <?php echo $selected_quarter === $q ? 'bg-white text-[var(--brand-color)] shadow-sm' : 'text-gray-400 hover:text-gray-600'; ?>">
                                <?php echo $q; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($selected_class_id && count($students) > 0): ?>
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    <input type="hidden" name="quarter" value="<?php echo $selected_quarter; ?>">
                    
                    <div class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden mb-10">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50/50">
                                    <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Learner</th>
                                    <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Numeric Score (0-100)</th>
                                    <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Equivalent Letter</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50/20 transition-colors">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-2xl bg-gray-100 overflow-hidden shadow-inner">
                                                    <?php if ($student['photo']): ?>
                                                        <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $student['photo']; ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-gray-300 font-black"><?php echo substr($student['first_name'], 0, 1); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter"><?php echo $student['student_code']; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <input type="number" step="0.01" min="0" max="100" name="score[<?php echo $student['id']; ?>]" value="<?php echo $marked_data[$student['id']]['score'] ?? ''; ?>" placeholder="0.00" oninput="updateLetter(this, 'letter_<?php echo $student['id']; ?>')" class="w-32 bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-black text-gray-900 transition-all outline-none">
                                        </td>
                                        <td class="px-8 py-6">
                                            <div id="letter_<?php echo $student['id']; ?>" class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl <?php 
                                                $lt = $marked_data[$student['id']]['grade_letter'] ?? '-';
                                                echo $lt === 'A' || $lt === 'A-' ? 'bg-green-50 text-green-600' : 
                                                    ($lt === 'F' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600');
                                            ?>">
                                                <?php echo $lt; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="save_grades" class="bg-gray-900 text-white px-12 py-5 rounded-[24px] font-black shadow-2xl hover:bg-black transition-all flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Save Academic Progress
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function updateLetter(input, targetId) {
            const score = parseFloat(input.value);
            const target = document.getElementById(targetId);
            if (isNaN(score)) {
                target.innerText = '-';
                target.className = 'w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl bg-gray-50 text-gray-300';
                return;
            }
            
            let letter = 'F';
            let colorClass = 'bg-red-50 text-red-600';
            
            if (score >= 90) { letter = 'A'; colorClass = 'bg-green-50 text-green-600'; }
            else if (score >= 85) { letter = 'A-'; colorClass = 'bg-green-50 text-green-600'; }
            else if (score >= 80) { letter = 'B+'; colorClass = 'bg-blue-50 text-blue-600'; }
            else if (score >= 75) { letter = 'B'; colorClass = 'bg-blue-50 text-blue-600'; }
            else if (score >= 70) { letter = 'B-'; colorClass = 'bg-blue-50 text-blue-600'; }
            else if (score >= 65) { letter = 'C+'; colorClass = 'bg-blue-50 text-blue-600'; }
            else if (score >= 60) { letter = 'C'; colorClass = 'bg-blue-50 text-blue-600'; }
            else if (score >= 55) { letter = 'C-'; colorClass = 'bg-blue-50 text-blue-600'; }
            else if (score >= 50) { letter = 'D'; colorClass = 'bg-blue-50 text-blue-600'; }
            
            target.innerText = letter;
            target.className = 'w-12 h-12 rounded-2xl flex items-center justify-center font-black text-xl ' + colorClass;
        }
    </script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
