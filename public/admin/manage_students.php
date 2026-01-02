<?php
/**
 * Student & Parent Management - Admin Portal
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

// Allow both 'admin' and 'student_admin'
requireAnyRole(['admin', 'student_admin']);

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

$success = '';
$error = '';
$new_creds = null;

// Handle CRUD ops
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_student') {
        $id = $_POST['student_id'] ?? null;
        
        // Form Fields
        $student_name = $_POST['student_name'];
        $parent_name = $_POST['parent_name'];
        $parent_phone = $_POST['parent_phone'];
        $password = $_POST['password'];
        $grade = $_POST['grade_level'];
        $section = $_POST['section'];
        $age = $_POST['age'];
        $gender = $_POST['gender'];

        // Split student name
        $name_parts = explode(' ', trim($student_name));
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? implode(' ', array_slice($name_parts, 1)) : 'Unknown';

        try {
            $pdo->beginTransaction();

            // 1. Handle Parent Account Creation/Update
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND role = 'parent' AND school_id = ? LIMIT 1");
            $stmt->execute([$parent_phone, $school_id]);
            $parent_user = $stmt->fetch();

            if ($parent_user) {
                $parent_id = $parent_user['id'];
                if ($password) {
                    $update = $pdo->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                    $update->execute([$parent_name, password_hash($password, PASSWORD_DEFAULT), $parent_id]);
                } else {
                    $update = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
                    $update->execute([$parent_name, $parent_id]);
                }
            } else {
                $temp_email = strtolower(str_replace(' ', '.', $parent_name)) . rand(100, 999) . "@ps.tracer";
                $stmt = $pdo->prepare("INSERT INTO users (school_id, name, email, password, role, phone) VALUES (?, ?, ?, ?, 'parent', ?)");
                $stmt->execute([$school_id, $parent_name, $temp_email, password_hash($password, PASSWORD_DEFAULT), $parent_phone]);
                $parent_id = $pdo->lastInsertId();
            }

            if ($id) {
                // Update Student
                $stmt = $pdo->prepare("
                    UPDATE students SET parent_id = ?, first_name = ?, last_name = ?, gender = ?, grade_level = ?, section = ?, age = ?
                    WHERE id = ? AND school_id = ?
                ");
                $stmt->execute([$parent_id, $first_name, $last_name, $gender, $grade, $section, $age, $id, $school_id]);
                $success = "Student profile updated.";
            } else {
                // Create Student
                $student_code = getNextStudentCode();
                
                $stmt = $pdo->prepare("
                    INSERT INTO students (school_id, parent_id, student_code, first_name, last_name, date_of_birth, gender, grade_level, section, age)
                    VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?)
                ");
                $stmt->execute([$school_id, $parent_id, $student_code, $first_name, $last_name, $gender, $grade, $section, $age]);
                $success = "Registration Successful!";
                $new_creds = [
                    'student_id' => $student_code,
                    'password' => $password
                ];
            }

            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    } elseif ($action === 'delete_student') {
        $id = $_POST['student_id'];
        try {
            $stmt = $pdo->prepare("UPDATE students SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $school_id]);
            $success = "Student record archived.";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch Students
try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as parent_name, u.phone as parent_phone 
        FROM students s 
        LEFT JOIN users u ON s.parent_id = u.id 
        WHERE s.school_id = ? AND s.deleted_at IS NULL
        ORDER BY s.grade_level ASC, s.last_name ASC
    ");
    $stmt->execute([$school_id]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Students Error: " . $e->getMessage());
    $students = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registry - <?php echo APP_NAME; ?></title>
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
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Educational Registry</h1>
            <div class="flex items-center gap-4">
                <button onclick="openStudentModal()" class="bg-[var(--brand-color)] text-[var(--brand-text-color)] px-6 py-3 rounded-2xl font-black text-sm shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Enroll Student
                </button>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <?php if ($success): ?>
                <div class="mb-8 p-5 bg-green-50 border-l-4 border-green-500 text-green-700 font-bold rounded-r-2xl">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-8 p-5 bg-red-50 border-l-4 border-red-500 text-red-700 font-bold rounded-r-2xl">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($students as $s): ?>
                    <div class="bg-white rounded-[40px] p-8 border border-gray-100 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all group">
                        <div class="flex items-start justify-between mb-6">
                            <div class="w-20 h-20 rounded-[32px] bg-gray-50 border-4 border-gray-50 overflow-hidden shadow-inner group-hover:scale-110 transition-transform">
                                <?php if ($s['photo']): ?>
                                    <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $s['photo']; ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center text-2xl font-black text-gray-200"><?php echo substr($s['first_name'], 0, 1); ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <span class="badge badge-purple italic uppercase text-[9px]"><?php echo htmlspecialchars($s['grade_level']); ?></span>
                                <span class="badge badge-info italic uppercase text-[9px]"><?php echo htmlspecialchars($s['student_code']); ?></span>
                            </div>
                        </div>
                        
                        <h3 class="text-xl font-black text-gray-900 mb-2"><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></h3>
                        <div class="space-y-3 mb-8">
                            <p class="text-xs font-bold text-gray-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Guardian: <span class="text-gray-600"><?php echo $s['parent_name'] ? htmlspecialchars($s['parent_name']) : 'Not Linked'; ?></span>
                            </p>
                            <p class="text-xs font-bold text-gray-400 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                Phone: <span class="text-gray-600"><?php echo htmlspecialchars($s['parent_phone'] ?: 'N/A'); ?></span>
                            </p>
                        </div>
                        
                        <div class="flex items-center justify-between pt-6 border-t border-gray-50 mt-auto">
                            <button onclick='editStudent(<?php echo json_encode($s); ?>)' class="text-xs font-black text-[var(--brand-color)] uppercase tracking-widest hover:underline">Revise Profile</button>
                            <form method="POST" onsubmit="return confirm('Archive this student record?')" class="inline">
                                <input type="hidden" name="action" value="delete_student">
                                <input type="hidden" name="student_id" value="<?php echo $s['id']; ?>">
                                <button type="submit" class="p-2 text-gray-300 hover:text-red-500 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Success Modal with Credentials -->
    <?php if ($new_creds): ?>
    <div id="credsModal" class="fixed inset-0 bg-black/60 backdrop-blur-md z-[60] flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-md overflow-hidden shadow-2xl animate-bounce-in">
            <div class="p-10 text-center">
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h3 class="text-2xl font-black text-gray-900 mb-2">Enrollment Success!</h3>
                <p class="text-gray-400 font-bold mb-8 uppercase tracking-widest text-[10px]">Student Access Credentials</p>
                
                <div class="space-y-4 mb-10">
                    <div class="bg-gray-50 p-6 rounded-3xl relative">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-2 text-left">Student ID</p>
                        <div class="flex items-center justify-between">
                            <span id="copy_id" class="text-xl font-black text-gray-900"><?php echo $new_creds['student_id']; ?></span>
                            <button onclick="copyToClipboard('copy_id')" class="p-2 text-[var(--brand-color)] hover:bg-white rounded-xl transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-3 8h4m-2 2v-4"></path></svg>
                            </button>
                        </div>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-3xl relative">
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-tighter mb-2 text-left">Guardian Password</p>
                        <div class="flex items-center justify-between">
                            <span id="copy_pass" class="text-xl font-black text-gray-900"><?php echo $new_creds['password']; ?></span>
                            <button onclick="copyToClipboard('copy_pass')" class="p-2 text-[var(--brand-color)] hover:bg-white rounded-xl transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m-3 8h4m-2 2v-4"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>

                <button onclick="document.getElementById('credsModal').remove()" class="w-full bg-gray-900 text-white py-5 rounded-2xl font-black shadow-xl hover:bg-black transition-all">
                    Dismiss and Continue
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Student Modal -->
    <div id="studentModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-2xl overflow-hidden shadow-2xl animate-slide-in">
            <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 id="modalTitle" class="text-2xl font-black text-gray-900">Student Enrollment</h2>
                <button onclick="closeStudentModal()" class="p-2 text-gray-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form method="POST" class="p-8 space-y-6">
                <input type="hidden" name="action" value="save_student">
                <input type="hidden" name="student_id" id="modal_student_id">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">1. Student Full Name</label>
                        <input type="text" name="student_name" id="modal_sname" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all" placeholder="e.g. Abebe Kebede">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">2. Parent/Guardian Name</label>
                        <input type="text" name="parent_name" id="modal_pname" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">3. Phone Number</label>
                        <input type="text" name="parent_phone" id="modal_phone" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">4. Access Password</label>
                        <input type="password" name="password" id="modal_pass" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">5. Class/Grade (5-8)</label>
                        <select name="grade_level" id="modal_grade" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all appearance-none">
                           <option value="Grade 5">Grade 5</option>
                           <option value="Grade 6">Grade 6</option>
                           <option value="Grade 7">Grade 7</option>
                           <option value="Grade 8">Grade 8</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">6. Section Hub</label>
                        <input type="text" name="section" id="modal_section" placeholder="e.g. A" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">7. Age</label>
                        <input type="number" name="age" id="modal_age" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">8. Sex</label>
                        <select name="gender" id="modal_gender" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all appearance-none">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4">
                    <button type="submit" class="w-full bg-gray-900 text-white py-5 rounded-[24px] font-black shadow-xl hover:bg-black transition-all">
                        Commit Enrollment Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const studentModal = document.getElementById('studentModal');
        const modalTitle = document.getElementById('modalTitle');
        const studentIdInput = document.getElementById('modal_student_id');
        
        const snameInput = document.getElementById('modal_sname');
        const pnameInput = document.getElementById('modal_pname');
        const phoneInput = document.getElementById('modal_phone');
        const passInput = document.getElementById('modal_pass');
        const gradeInput = document.getElementById('modal_grade');
        const sectionInput = document.getElementById('modal_section');
        const ageInput = document.getElementById('modal_age');
        const genderInput = document.getElementById('modal_gender');

        function openStudentModal() {
            modalTitle.innerText = "New Student Enrollment";
            studentIdInput.value = "";
            snameInput.value = "";
            pnameInput.value = "";
            phoneInput.value = "";
            passInput.value = "";
            passInput.required = true;
            gradeInput.value = "Grade 5";
            sectionInput.value = "";
            ageInput.value = "";
            genderInput.value = "male";
            studentModal.classList.remove('hidden');
        }

        function closeStudentModal() {
            studentModal.classList.add('hidden');
        }

        function editStudent(s) {
            modalTitle.innerText = "Revise Student Portfolio";
            studentIdInput.value = s.id;
            snameInput.value = s.first_name + ' ' + s.last_name;
            pnameInput.value = s.parent_name || "";
            phoneInput.value = s.parent_phone || "";
            passInput.value = ""; 
            passInput.required = false; 
            gradeInput.value = s.grade_level;
            sectionInput.value = s.section || "";
            ageInput.value = s.age || "";
            genderInput.value = s.gender;
            studentModal.classList.remove('hidden');
        }

        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).innerText;
            navigator.clipboard.writeText(text).then(() => {
                alert('Copied to clipboard: ' + text);
            });
        }
    </script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
