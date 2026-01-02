<?php
/**
 * Student Registration - Parent Portal
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../middleware/auth_middleware.php';
require_once __DIR__ . '/../../includes/functions.php';

requireRole('parent');

$user = getCurrentUser();
$school_id = $user['school_id'];
$branding = getSchoolBranding($school_id);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $desired_grade = $_POST['desired_grade_level'];
    
    // Validate Ethiopian Grade Level (5-8)
    if (!isValidEthiopianGrade($desired_grade)) {
        $error = "System restricted to Primary Grades 5-8. Please select a valid grade.";
    } else {
        // Auto-generate a temporary student code
        $student_code = "REQ-" . strtoupper(substr(uniqid(), -6));

        try {
            $stmt = $pdo->prepare("
                INSERT INTO students (school_id, parent_id, student_code, first_name, last_name, date_of_birth, gender, grade_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$school_id, $user['id'], $student_code, $first_name, $last_name, $dob, $gender, $desired_grade]);
            $success = "Registration request for " . htmlspecialchars($first_name) . " has been submitted for administrative review.";
        } catch (PDOException $e) {
            $error = "Registration error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Student - <?php echo APP_NAME; ?></title>
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
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Student Registration</h1>
            <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-8 max-w-4xl mx-auto">
            <?php if ($success): ?>
                <div class="mb-10 p-8 bg-green-50 border-l-8 border-green-500 rounded-r-[32px] animate-slide-in">
                    <h3 class="text-xl font-black text-green-800 mb-2">Registration Submitted!</h3>
                    <p class="text-green-700 font-semibold opacity-80 leading-relaxed"><?php echo $success; ?></p>
                    <div class="mt-6">
                        <a href="dashboard.php" class="bg-green-500 text-white px-6 py-3 rounded-xl font-bold text-sm inline-block shadow-lg shadow-green-200">Return to Terminal</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-[40px] p-10 border border-gray-100 shadow-sm">
                    <div class="mb-10 text-center">
                        <div class="w-20 h-20 bg-gray-50 rounded-[32px] flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-[var(--brand-color)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <h2 class="text-3xl font-black text-gray-900 mb-4">Enroll Your Child</h2>
                        <p class="text-gray-400 font-bold max-w-md mx-auto leading-relaxed">Provide the student's background information. Our administrative team will finalize the enrollment and section assignment.</p>
                    </div>

                    <form method="POST" class="space-y-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Legal First Name</label>
                                <input type="text" name="first_name" required placeholder="Enter name" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Legal Last Name</label>
                                <input type="text" name="last_name" required placeholder="Enter surname" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Date of Birth</label>
                                <input type="date" name="dob" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                            </div>
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Gender Registry</label>
                                <select name="gender" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none appearance-none transition-all">
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Target Grade Enrollment (5-8)</label>
                                <select name="desired_grade_level" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none appearance-none transition-all">
                                    <option value="Grade 5">Grade 5</option>
                                    <option value="Grade 6">Grade 6</option>
                                    <option value="Grade 7">Grade 7</option>
                                    <option value="Grade 8">Grade 8</option>
                                </select>
                            </div>
                        </div>

                        <div class="pt-6">
                            <button type="submit" class="w-full bg-[var(--brand-color)] text-[var(--brand-text-color)] py-6 rounded-[32px] font-black text-lg shadow-2xl shadow-[var(--brand-color)]/30 hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-4">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Submit Official Request
                            </button>
                            <p class="text-center text-[10px] font-black text-gray-300 uppercase tracking-[0.3em] mt-6">Secure Institutional Data Submission</p>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
