<?php
/**
 * User Management (Staff/Teachers) - Admin Portal
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

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $id = $_POST['user_id'] ?? null;
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $phone = $_POST['phone'];
        $password = $_POST['password'] ?? '';

        try {
            if ($id) {
                // Update
                if ($password) {
                    $stmt = $pdo->prepare("
                        UPDATE users SET name = ?, email = ?, role = ?, phone = ?, password = ?
                        WHERE id = ? AND school_id = ?
                    ");
                    $stmt->execute([$name, $email, $role, $phone, password_hash($password, PASSWORD_DEFAULT), $id, $school_id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users SET name = ?, email = ?, role = ?, phone = ?
                        WHERE id = ? AND school_id = ?
                    ");
                    $stmt->execute([$name, $email, $role, $phone, $id, $school_id]);
                }
                $success = "User updated successfully.";
            } else {
                // Create
                $stmt = $pdo->prepare("
                    INSERT INTO users (school_id, name, email, password, role, phone)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$school_id, $name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $phone]);
                $success = "New staff member added successfully.";
            }
        } catch (PDOException $e) {
            $error = "Error saving user: " . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['user_id'];
        try {
            $stmt = $pdo->prepare("UPDATE users SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND school_id = ?");
            $stmt->execute([$id, $school_id]);
            $success = "User deactivated successfully.";
        } catch (PDOException $e) {
            $error = "Error deleting user: " . $e->getMessage();
        }
    }
}

// Fetch all staff (Admin and Teachers)
try {
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE school_id = ? AND role IN ('admin', 'teacher', 'student_admin') AND deleted_at IS NULL
        ORDER BY role ASC, name ASC
    ");
    $stmt->execute([$school_id]);
    $staff_members = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Manage Users Error: " . $e->getMessage());
    $staff_members = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Directory - <?php echo APP_NAME; ?></title>
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
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Staff Directory</h1>
            <div class="flex items-center gap-4">
                <button onclick="openModal()" class="bg-[var(--brand-color)] text-[var(--brand-text-color)] px-6 py-3 rounded-2xl font-black text-sm shadow-lg shadow-[var(--brand-color)]/20 hover:scale-[1.05] active:scale-95 transition-all flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add Staff
                </button>
                <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto">
            <?php if ($success): ?>
                <div class="mb-8 p-5 bg-green-50 border-l-4 border-green-500 text-green-700 font-bold rounded-r-2xl shadow-sm">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-[40px] border border-gray-100 shadow-sm overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50/50">
                        <tr>
                            <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Full Name</th>
                            <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Institutional Email</th>
                            <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest">Role</th>
                            <th class="px-8 py-6 text-xs font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        <?php foreach ($staff_members as $staff): ?>
                            <tr class="hover:bg-gray-50/30 transition-colors">
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center font-black text-[var(--brand-color)]">
                                            <?php echo substr($staff['name'], 0, 1); ?>
                                        </div>
                                        <span class="font-bold text-gray-900"><?php echo htmlspecialchars($staff['name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-8 py-6 text-gray-600 font-medium"><?php echo htmlspecialchars($staff['email']); ?></td>
                                <td class="px-8 py-6">
                                    <span class="badge <?php 
                                        echo $staff['role'] === 'admin' ? 'badge-purple' : ($staff['role'] === 'student_admin' ? 'badge-warning' : 'badge-info'); 
                                    ?> italic uppercase text-[10px]">
                                        <?php 
                                            echo $staff['role'] === 'student_admin' ? 'Normal Admin' : $staff['role']; 
                                        ?>
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick='editUser(<?php echo json_encode($staff); ?>)' class="p-2 text-gray-400 hover:text-[var(--brand-color)] transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Deactivate this user account?')" class="inline">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo $staff['id']; ?>">
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-500 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Form -->
    <div id="userModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-[40px] w-full max-w-lg overflow-hidden shadow-2xl animate-slide-in">
            <div class="p-8 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                <h2 id="modalTitle" class="text-2xl font-black text-gray-900">Configure Staff</h2>
                <button onclick="closeModal()" class="p-2 text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form method="POST" class="p-8 space-y-6">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="user_id" id="modal_user_id">
                
                <div class="grid grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Full Name</label>
                        <input type="text" name="name" id="modal_name" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Login Email</label>
                        <input type="email" name="email" id="modal_email" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Role Designation</label>
                        <select name="role" id="modal_role" required class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all appearance-none">
                            <option value="teacher">Teacher / Faculty</option>
                            <option value="student_admin">Normal Admin (Student Only)</option>
                            <option value="admin">System Admin</option>
                        </select>
                    </div>

                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Contact Phone</label>
                        <input type="text" name="phone" id="modal_phone" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                    
                    <div class="col-span-2 sm:col-span-1">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Password</label>
                        <input type="password" name="password" id="modal_password" placeholder="Leave blank to keep" class="w-full bg-gray-50 border-2 border-transparent focus:border-[var(--brand-color)] rounded-2xl px-6 py-4 font-bold text-gray-900 outline-none transition-all">
                    </div>
                </div>

                <button type="submit" class="w-full bg-gray-900 text-white py-5 rounded-[24px] font-black shadow-xl hover:bg-black transition-all">
                    Finalize Configuration
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('userModal');
        const modalTitle = document.getElementById('modalTitle');
        const userIdInput = document.getElementById('modal_user_id');
        const nameInput = document.getElementById('modal_name');
        const emailInput = document.getElementById('modal_email');
        const roleInput = document.getElementById('modal_role');
        const phoneInput = document.getElementById('modal_phone');
        const passInput = document.getElementById('modal_password');

        function openModal() {
            modalTitle.innerText = "Add New Staff";
            userIdInput.value = "";
            nameInput.value = "";
            emailInput.value = "";
            roleInput.value = "teacher";
            phoneInput.value = "";
            passInput.placeholder = "Enter password";
            passInput.required = true;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
        }

        function editUser(user) {
            modalTitle.innerText = "Edit Professional Profile";
            userIdInput.value = user.id;
            nameInput.value = user.name;
            emailInput.value = user.email;
            roleInput.value = user.role;
            phoneInput.value = user.phone || "";
            passInput.placeholder = "Leave blank to keep same";
            passInput.required = false;
            modal.classList.remove('hidden');
        }
    </script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
