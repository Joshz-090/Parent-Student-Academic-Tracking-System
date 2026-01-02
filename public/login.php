<?php
/**
 * Login Page
 * Multi-School Parent-Student Tracer System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Redirect if already logged in
redirectIfLoggedIn();

// Initialize session
initSession();

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = login($email, $password);
        
        if ($result['success']) {
            // Redirect based on role
            switch ($result['role']) {
                case 'admin':
                    redirect(APP_URL . '/admin/dashboard.php');
                    break;
                case 'teacher':
                    redirect(APP_URL . '/teacher/dashboard.php');
                    break;
                case 'parent':
                    redirect(APP_URL . '/parent/dashboard.php');
                    break;
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    <style>
        .login-bg {
            background: radial-gradient(circle at top right, #e0f2fe 0%, #f1f5f9 50%, #e0f2fe 100%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Decorative background elements -->
    <div class="absolute -top-24 -left-24 w-96 h-96 bg-blue-400/10 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-indigo-400/10 rounded-full blur-3xl"></div>

    <div class="w-full max-w-[440px] relative z-10">
        <!-- Logo and Title -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-600 shadow-xl shadow-blue-500/20 rounded-[28px] mb-6 transform hover:rotate-6 transition-transform">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
            </div>
            <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2"><?php echo APP_NAME; ?></h1>
            <p class="text-gray-500 font-bold uppercase tracking-widest text-[10px]">Secure Institutional Access</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-[40px] shadow-2xl p-10">
            <?php if ($error): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-2xl flex items-center gap-3">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <p class="font-bold text-sm"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" class="space-y-6">
                <!-- Email / Student ID -->
                <div>
                    <label for="email" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 px-1">Institutional ID (Email or Student ID)</label>
                    <input 
                        type="text" 
                        id="email" 
                        name="email" 
                        required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-6 py-4 bg-gray-50/50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl font-bold text-gray-900 transition-all outline-none"
                        placeholder="e.g. 1001 or admin@school.edu"
                    >
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 px-1">Access Token / Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-6 py-4 bg-gray-50/50 border-transparent focus:bg-white focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 rounded-2xl font-bold text-gray-900 transition-all outline-none"
                        placeholder="••••••••"
                    >
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-5 rounded-2xl shadow-xl shadow-blue-500/30 transition-all duration-300 transform hover:scale-[1.02] active:scale-[0.98]"
                >
                    Initialize Connection
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-10 pt-8 border-t border-gray-100">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 text-center">System Access Guide</p>
                <div class="grid grid-cols-1 gap-3 text-center">
                    <p class="text-[9px] font-bold text-gray-400 italic">Parents: Sign in using Student ID and Password set by Admin</p>
                    <p class="text-[9px] font-bold text-gray-400 italic">Admin: Log in with official institutional email</p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="text-center mt-10 text-[10px] font-black text-gray-400 uppercase tracking-widest">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> Kernel v<?php echo APP_VERSION; ?>
        </p>
    </div>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
