<?php
/**
 * Class & Enrollment Management - Admin Portal
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('admin');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

$success = '';
$error = '';

// Handle CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_class') {
        $id = $_POST['class_id'] ?? null;
        $teacher_id = $_POST['teacher_id'] ?: null;
        $subject = $_POST['subject_name'];
        $grade = $_POST['grade_level'];
        $section = $_POST['section'];
        $schedule = $_POST['schedule'];
        $room = $_POST['room_number'];

        try {
            if ($id) {
                $stmt = $pdo->prepare("
                    UPDATE classes SET teacher_id = ?, subject_name = ?, grade_level = ?, section = ?, schedule = ?, room_number = ?
                    WHERE id = ? AND school_id = ?
                ");
                $stmt->execute([$teacher_id, $subject, $grade, $section, $schedule, $room, $id, $school_id]);
                $success = "Class profile updated.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO classes (school_id, teacher_id, subject_name, grade_level, section, schedule, room_number)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$school_id, $teacher_id, $subject, $grade, $section, $schedule, $room]);
                $success = "New learning session established.";
            }
        } catch (PDOException $e) { $error = "Error: " . $e->getMessage(); }
    } elseif ($action === 'enroll_students') {
        // This is now legacy/manual override but we recommend automatic Grade/Section mapping.
        $error = "Enrollment is now automated based on Grade/Section assignments in Student Profiles.";
    } elseif ($action === 'delete_class') {
        $id = $_POST['class_id'];
        try {
            $stmt = $pdo->prepare("UPDATE classes SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $school_id]);
            $success = "Learning session archived successfully.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Classes
try {
    $stmt = $pdo->prepare("
        SELECT c.*, u.name as teacher_name 
        FROM classes c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        WHERE c.school_id = ? AND c.deleted_at IS NULL
        ORDER BY c.grade_level ASC, c.subject_name ASC
    ");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();

    // Fetch Teachers
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE school_id = ? AND role = 'teacher' AND deleted_at IS NULL");
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();

    // Fetch All Students for Enrollment
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, grade_level FROM students WHERE school_id = ? AND deleted_at IS NULL");
    $stmt->execute([$school_id]);
    $all_students = $stmt->fetchAll();
} catch (PDOException $e) { error_log($e->getMessage()); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curriculum Control - <?php echo APP_NAME; ?></title>
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

    <main class="ml-0 md:ml-[280px] min-h-screen">
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Curriculum Hub</h1>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <!-- Grade Selection Buttons -->
            <div class="mb-12">
                <h2 class="text-2xl font-black text-gray-900 mb-6 flex items-center gap-3">
                    <svg class="w-6 h-6 text-[var(--brand-color)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Initialize Course
                </h2>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-6">
                    <?php foreach (['Grade 5', 'Grade 6', 'Grade 7', 'Grade 8'] as $g): ?>
                        <button onclick="openClassModal('<?php echo $g; ?>')" class="group relative bg-white border border-gray-100 p-6 rounded-[32px] shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all text-left overflow-hidden">
                            <div class="relative z-10">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 group-hover:text-[var(--brand-color)] transition-colors">Select Class</p>
                                <h3 class="text-xl font-black text-gray-900"><?php echo $g; ?></h3>
                            </div>
                            <div class="absolute -right-4 -bottom-4 text-gray-50 group-hover:text-[var(--brand-color)]/10 transition-colors">
                                <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 20 20"><path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path></svg>
                            </div>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mb-8 flex items-center justify-between">
                <h2 class="text-2xl font-black text-gray-900">Active Curriculum</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($classes as $c): ?>
                    <div class="bg-white rounded-[40px] p-8 border border-gray-100 shadow-sm flex flex-col md:flex-row gap-8">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="badge badge-info italic uppercase"><?php echo htmlspecialchars($c['grade_level']); ?></span>
                                <span class="badge badge-purple italic uppercase"><?php echo htmlspecialchars($c['section']); ?></span>
                            </div>
                            <h3 class="text-2xl font-black text-gray-900 mb-2"><?php echo htmlspecialchars($c['subject_name']); ?></h3>
                            <p class="text-sm font-bold text-gray-400 mb-6 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Lead Educator: <span class="text-[var(--brand-color)]"><?php echo $c['teacher_name'] ? htmlspecialchars($c['teacher_name']) : 'Unassigned'; ?></span>
                            </p>
                            
                            <div class="grid grid-cols-2 gap-4 mb-8">
                                <div class="bg-gray-50 p-4 rounded-2xl">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Schedule</p>
                                    <p class="text-xs font-bold text-gray-700"><?php echo htmlspecialchars($c['schedule'] ?: 'N/A'); ?></p>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-2xl">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Location</p>
                                    <p class="text-xs font-bold text-gray-700">Room <?php echo htmlspecialchars($c['room_number'] ?: 'N/A'); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-4">
                                <button onclick='editClass(<?php echo json_encode($c); ?>)' class="text-xs font-black text-gray-400 hover:text-gray-900 uppercase tracking-widest transition-colors">Adjust Profile</button>
                                <button onclick='openRosterModal("<?php echo $c['grade_level']; ?>", "<?php echo $c['section']; ?>", "<?php echo htmlspecialchars($c['subject_name']); ?>")' class="bg-[var(--brand-color)] text-[var(--brand-text-color)] px-6 py-2 rounded-xl font-bold text-xs shadow-lg shadow-[var(--brand-color)]/20 mr-auto transition-colors duration-300">Automatic Roster</button>
                                
                                <form method="POST" onsubmit="return confirm('Archive this learning session?')" class="inline">
                                    <input type="hidden" name="action" value="delete_class">
                                    <input type="hidden" name="class_id" value="<?php echo $c['id']; ?>">
                                    <button type="submit" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Class Modal -->
    <div id="classModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-lg overflow-hidden shadow-2xl animate-slide-in">
            <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 id="modalTitle" class="text-2xl font-black text-gray-900">Configure Session</h2>
                <button onclick="closeClassModal()" class="p-2 text-gray-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <form method="POST" class="p-8 space-y-6">
                <input type="hidden" name="action" value="save_class">
                <input type="hidden" name="class_id" id="modal_class_id">
                <input type="hidden" name="grade_level" id="modal_grade">
                <input type="hidden" name="schedule" id="modal_schedule" value="Daily">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">1. Subject Name</label>
                        <input type="text" name="subject_name" id="modal_subject" required placeholder="e.g. Mathematics, English..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">2. Assigned Teacher</label>
                        <select name="teacher_id" id="modal_teacher" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none appearance-none">
                            <option value="">-- Choose Instructor --</option>
                            <?php foreach ($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">3. Section</label>
                        <input type="text" name="section" id="modal_section" required placeholder="A, B, C..." class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">4. Room ID</label>
                        <input type="text" name="room_number" id="modal_room" required placeholder="Rm-101" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none">
                    </div>
                </div>
                <!-- Grade display indicator -->
                <div class="bg-[var(--brand-color)]/5 p-4 rounded-2xl border border-[var(--brand-color)]/10 text-center">
                    <p class="text-[10px] font-black text-[var(--brand-color)] uppercase tracking-widest">Assigning to</p>
                    <p id="grade_indicator" class="text-lg font-black text-gray-900"></p>
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white py-5 rounded-[24px] font-black shadow-xl hover:bg-black transition-colors">Create Session</button>
            </form>
        </div>
    </div>

    <!-- Roster Modal -->
    <div id="rosterModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-xl overflow-hidden shadow-2xl animate-slide-in">
            <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 class="text-2xl font-black text-gray-900">Roster: <span id="roster_class_name" class="text-[var(--brand-color)]"></span></h2>
                <button onclick="closeRosterModal()" class="p-2 text-gray-400"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
            </div>
            <div class="p-8 space-y-6">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest bg-gray-50 p-4 rounded-xl">Note: Students are automatically enrolled based on matching Grade/Section in their profiles.</p>
                <div id="roster_list" class="max-h-80 overflow-y-auto space-y-2 pr-2 custom-scrollbar">
                    <!-- Loaded via JS -->
                </div>
                <div class="pt-4">
                    <button onclick="closeRosterModal()" class="w-full bg-gray-900 text-white py-5 rounded-[24px] font-black">Close Register</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const cModal = document.getElementById('classModal');
        const rModal = document.getElementById('rosterModal');
        const allStudents = <?php echo json_encode($all_students); ?>;
        
        function openClassModal(grade) {
            document.getElementById('modalTitle').innerText = "Design Learning Session";
            document.getElementById('modal_class_id').value = "";
            document.getElementById('modal_subject').value = "";
            document.getElementById('modal_teacher').value = "";
            document.getElementById('modal_grade').value = grade;
            document.getElementById('grade_indicator').innerText = grade;
            document.getElementById('modal_section').value = "";
            document.getElementById('modal_room').value = "";
            cModal.classList.remove('hidden');
        }

        function closeClassModal() { cModal.classList.add('hidden'); }
        function closeRosterModal() { rModal.classList.add('hidden'); }

        function editClass(c) {
            document.getElementById('modalTitle').innerText = "Adjust Session Profile";
            document.getElementById('modal_class_id').value = c.id;
            document.getElementById('modal_subject').value = c.subject_name;
            document.getElementById('modal_teacher').value = c.teacher_id || "";
            document.getElementById('modal_grade').value = c.grade_level;
            document.getElementById('grade_indicator').innerText = c.grade_level;
            document.getElementById('modal_section').value = c.section;
            document.getElementById('modal_schedule').value = c.schedule || "Daily";
            document.getElementById('modal_room').value = c.room_number || "";
            cModal.classList.remove('hidden');
        }

        function openRosterModal(grade, section, subject) {
            document.getElementById('roster_class_name').innerText = subject;
            const rosterList = document.getElementById('roster_list');
            rosterList.innerHTML = "";
            
            const matched = allStudents.filter(s => s.grade_level === grade && (s.section === section || !section));
            
            if (matched.length === 0) {
                rosterList.innerHTML = '<p class="text-center py-10 text-gray-400 font-bold">No students matched to ' + grade + ' ' + section + '.</p>';
            } else {
                matched.forEach(s => {
                    rosterList.innerHTML += `
                        <div class="flex items-center gap-4 p-4 rounded-2xl bg-gray-50 border border-gray-100">
                            <div class="w-10 h-10 rounded-full bg-[var(--brand-color)] text-[var(--brand-text-color)] flex items-center justify-center font-black text-xs transition-colors duration-300">
                                ${s.first_name[0]}${s.last_name[0]}
                            </div>
                            <div>
                                <p class="font-bold text-gray-900">${s.first_name} ${s.last_name}</p>
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">${s.grade_level}</p>
                            </div>
                        </div>
                    `;
                });
            }
            rModal.classList.remove('hidden');
        }
    </script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
