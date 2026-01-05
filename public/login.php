<?php
/**
 * Login Page
 * Multi-School Parent-Student Tracer System
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../middleware/auth_middleware.php';

// Redirect if already logged in
redirectIfLoggedIn();

// Initialize session
// Initialize session
initSession();

// Fetch Branding
require_once __DIR__ . '/../includes/functions.php';
$branding = getDefaultSchoolBranding();
$app_name = $branding['school_name'] ?? APP_NAME;
$primary_color = $branding['primary_color'] ?? '#2563EB'; // Default blue-600
$logo = $branding['logo'] ?? null;

$error = '';

/**
 * Helper to convert hex to rgb values (e.g. "255, 255, 255")
 */
function hexToRgbVals($hex) {
    // Remove # if present
    $hex = str_replace("#", "", $hex);
    
    // Check if shorthand hex
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    
    return "$r, $g, $b";
}

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
    <title>Sign In - <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .login-bg {
            background-color: #f8fafc;
            background-image: 
                radial-gradient(at 0% 0%, hsla(210,100%,96%,1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(220,100%,96%,1) 0, transparent 50%);
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 1);
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.05), 
                0 2px 4px -1px rgba(0, 0, 0, 0.03),
                0 20px 25px -5px rgba(0, 0, 0, 0.05); /* Softer, multi-layer shadow */
        }
        :root {
            --brand-color: <?php echo $primary_color; ?>;
        }
        /* Custom Ring Utility for dynamic color */
        .focus-ring:focus-within {
            ring-color: var(--brand-color);
            border-color: var(--brand-color);
            box-shadow: 0 0 0 4px rgba(<?php echo hexToRgbVals($primary_color); ?>, 0.1); /* Soft glow */
        }
    </style>
</head>
<body class="login-bg min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    
    <!-- Decorative background elements -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[1000px] h-[600px] rounded-full blur-3xl pointer-events-none -z-10 opacity-30" style="background-color: var(--brand-color);"></div>

    <div class="w-full max-w-[420px] relative z-10 transition-all duration-500 ease-out transform translate-y-4">
        <!-- Logo and Title -->
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white shadow-xl shadow-slate-200 rounded-2xl mb-6 overflow-hidden transform hover:-translate-y-1 transition-transform border border-slate-100">
                <?php if ($logo): ?>
                    <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $logo; ?>" alt="Logo" class="w-full h-full object-cover">
                <?php else: ?>
                    <svg class="w-8 h-8 text-[var(--brand-color)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mb-1"><?php echo htmlspecialchars($app_name); ?></h1>
            <p class="text-slate-500 font-medium text-xs uppercase tracking-wider">Secure Access Portal</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-3xl p-8 sm:p-10">
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-100 text-red-600 p-4 mb-6 rounded-xl flex items-center gap-3 text-sm font-semibold animate-pulse">
                    <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" class="space-y-5">
                <!-- Email / Student ID -->
                <div class="space-y-1.5 Group">
                    <label for="email" class="block text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Institutional ID</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                        </div>
                        <input 
                            type="text" 
                            id="email" 
                            name="email" 
                            required
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                            class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-4 focus:ring-opacity-20 transition-all duration-200"
                            style="--tw-ring-color: var(--brand-color); caret-color: var(--brand-color);"
                            placeholder="Email or Student ID"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="space-y-1.5 Group">
                    <label for="password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider ml-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                        </div>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            class="block w-full pl-11 pr-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-900 font-semibold focus:bg-white focus:outline-none focus:ring-4 focus:ring-opacity-20 transition-all duration-200"
                            style="--tw-ring-color: var(--brand-color); caret-color: var(--brand-color);"
                            placeholder="••••••••"
                        >
                    </div>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit"
                    class="w-full text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 active:scale-[0.98] flex items-center justify-center gap-2 mt-2"
                    style="background-color: var(--brand-color); box-shadow: 0 10px 15px -3px rgba(<?php echo hexToRgbVals($primary_color); ?>, 0.3);"
                >
                    <span>Sign In</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </button>
            </form>
            
            <div class="mt-8 text-center">
                 <a href="<?php echo APP_URL; ?>/../index.html" class="text-xs font-bold text-slate-400 hover:text-slate-600 transition-colors">← Back to Home</a>
            </div>
        </div>  
        <!-- Demo Credentials -->
        <div class="mt-10 pt-8 border-t border-gray-100">
            <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 text-center">System Access Guide</p>
            <div class="grid grid-cols-1 gap-3 text-center">
                <p class="text-[9px] font-bold text-gray-400 italic">Parents: Sign in using Student ID and Password set by Admin</p>
                <p class="text-[9px] font-bold text-gray-400 italic">Admin: Log in with official institutional email</p>
            </div>
        </div>
    
        <!-- Footer -->
        <p class="text-center mt-10 text-[10px] font-black text-gray-400 uppercase tracking-widest">
            &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_name); ?> Kernel v<?php echo APP_VERSION; ?>
        </p>
    </div>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
</body>
</html>
