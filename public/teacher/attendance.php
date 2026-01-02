<?php
/**
 * Record Attendance - Teacher Portal
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $class_id = $_POST['class_id'];
    $date = $_POST['attendance_date'];
    $attendance_data = $_POST['status'] ?? []; // student_id => status

    try {
        $pdo->beginTransaction();
        
        foreach ($attendance_data as $student_id => $status) {
            $notes = $_POST['notes'][$student_id] ?? '';
            
            $stmt = $pdo->prepare("
                INSERT INTO attendance (student_id, class_id, date, status, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                status = VALUES(status), 
                notes = VALUES(notes),
                recorded_by = VALUES(recorded_by)
            ");
            $stmt->execute([$student_id, $class_id, $date, $status, $notes, $user['id']]);
        }
        
        $pdo->commit();
        $success = "Attendance recorded successfully for " . formatDate($date);
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error recording attendance: " . $e->getMessage();
    }
}

// Fetch Teacher's Classes
try {
    $stmt = $pdo->prepare("
        SELECT id, subject_name, grade_level, section, schedule 
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
        
        // Get already marked attendance for today (default) or selected date
        $target_date = $_GET['date'] ?? date('Y-m-d');
        $stmt = $pdo->prepare("SELECT student_id, status, notes FROM attendance WHERE class_id = ? AND date = ?");
        $stmt->execute([$selected_class_id, $target_date]);
        $existing_attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $marked_data = [];
        foreach ($existing_attendance as $row) {
            $marked_data[$row['student_id']] = $row;
        }
    }
} catch (PDOException $e) {
    error_log("Attendance Page Error: " . $e->getMessage());
    $classes = $students = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Attendance - <?php echo APP_NAME; ?></title>
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
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Attendance Recording</h1>
            <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 font-bold rounded-r-xl">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 font-bold rounded-r-xl">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="mb-10 flex flex-wrap gap-6 items-end bg-white p-8 rounded-[40px] shadow-sm border border-gray-100">
                <div class="flex-1 min-w-[250px]">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Select Class Portfolio</label>
                    <div class="relative">
                        <select onchange="window.location.href='?class_id='+this.value" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 appearance-none transition-all outline-none">
                            <option value="">-- Select Your Class --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $class['id'] == $selected_class_id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['subject_name']); ?> - <?php echo htmlspecialchars($class['grade_level']); ?> (<?php echo htmlspecialchars($class['section']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </div>
                    </div>
                </div>
                
                <div class="w-full sm:w-auto">
                    <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Recording Date</label>
                    <input type="date" value="<?php echo $target_date; ?>" onchange="window.location.href='?class_id=<?php echo $selected_class_id; ?>&date='+this.value" class="bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 transition-all outline-none">
                </div>
            </div>

            <?php if ($selected_class_id && count($students) > 0): ?>
                <form method="POST">
                    <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo $target_date; ?>">
                    
                    <div class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden mb-10">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Student Profile</th>
                                    <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Attendance Status</th>
                                    <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-[0.2em]">Teacher Notes</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50/30 transition-colors">
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-4">
                                                <div class="w-12 h-12 rounded-2xl bg-gray-100 overflow-hidden shrink-0">
                                                    <?php if ($student['photo']): ?>
                                                        <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $student['photo']; ?>" class="w-full h-full object-cover">
                                                    <?php else: ?>
                                                        <div class="w-full h-full flex items-center justify-center text-gray-300 font-black">
                                                            <?php echo substr($student['first_name'], 0, 1); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic"><?php echo $student['student_code']; ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <div class="flex items-center gap-2">
                                                <?php 
                                                $status = $marked_data[$student['id']]['status'] ?? 'present';
                                                $options = [
                                                    'present' => ['bg' => 'bg-green-50', 'text' => 'text-green-600', 'border' => 'border-green-100'],
                                                    'late' => ['bg' => 'bg-amber-50', 'text' => 'text-amber-600', 'border' => 'border-amber-100'],
                                                    'absent' => ['bg' => 'bg-red-50', 'text' => 'text-red-600', 'border' => 'border-red-100'],
                                                    'excused' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-600', 'border' => 'border-blue-100']
                                                ];
                                                foreach ($options as $val => $style):
                                                ?>
                                                    <label class="cursor-pointer">
                                                        <input type="radio" name="status[<?php echo $student['id']; ?>]" value="<?php echo $val; ?>" <?php echo $status === $val ? 'checked' : ''; ?> class="hidden peer">
                                                        <div class="px-4 py-2 rounded-xl border-2 border-transparent peer-checked:border-current peer-checked:<?php echo $style['bg']; ?> <?php echo $style['text']; ?> font-bold text-sm transition-all hover:bg-gray-50">
                                                            <?php echo ucfirst($val); ?>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </td>
                                        <td class="px-8 py-6">
                                            <input type="text" name="notes[<?php echo $student['id']; ?>]" value="<?php echo htmlspecialchars($marked_data[$student['id']]['notes'] ?? ''); ?>" placeholder="Add optional note..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-xl px-4 py-3 text-sm font-semibold text-gray-700 transition-all outline-none">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" name="mark_attendance" class="bg-[var(--brand-color)] text-white px-10 py-5 rounded-[24px] font-black shadow-xl shadow-[var(--brand-color)]/20 hover:scale-105 active:scale-95 transition-all flex items-center gap-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Commit Journal Entries
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div class="text-center py-20 bg-white rounded-[40px] border border-gray-100">
                    <p class="text-gray-400 font-bold uppercase tracking-widest text-sm">No students assigned to this class portfolio.</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
