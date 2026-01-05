<?php
/**
 * Landing Page - Dynamic Branding
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db_connection.php';
require_once __DIR__ . '/includes/functions.php';

// Fetch Default School Branding
$branding = getDefaultSchoolBranding();
$app_name = $branding['school_name'] ?? 'PS-Tracer';
$primary_color = $branding['primary_color'] ?? '#3B82F6';
$logo = $branding['logo'] ?? null;
$footer_text = $branding['footer_text'] ?? 'Empowering Education through Technology';

// Convert hex to rgb for rgba usage if needed
function hexToRgbVals($hex) {
    $hex = str_replace("#", "", $hex);
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
$brand_rgb = hexToRgbVals($primary_color);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - <?php echo htmlspecialchars($app_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.5);
        }
        .hero-pattern {
            background-color: #ffffff;
            background-image: radial-gradient(<?php echo $primary_color; ?> 0.5px, transparent 0.5px), radial-gradient(<?php echo $primary_color; ?> 0.5px, #ffffff 0.5px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            opacity: 0.1;
        }
        :root {
            --brand-color: <?php echo $primary_color; ?>;
        }
    </style>
</head>
<body class="bg-slate-50 relative overflow-x-hidden">

    <!-- Background Decoration -->
    <div class="fixed inset-0 hero-pattern pointer-events-none"></div>
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-[800px] h-[500px] rounded-full blur-3xl pointer-events-none -z-10" style="background-color: rgba(<?php echo $brand_rgb; ?>, 0.2);"></div>

    <!-- Navigation -->
    <nav class="fixed top-0 w-full glass-nav z-50">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white shadow-lg overflow-hidden" style="background-color: var(--brand-color); box-shadow: 0 10px 15px -3px rgba(<?php echo $brand_rgb; ?>, 0.2);">
                    <?php if ($logo): ?>
                        <img src="public/assets/uploads/<?php echo $logo; ?>" alt="Logo" class="w-full h-full object-cover">
                    <?php else: ?>
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    <?php endif; ?>
                </div>
                <div>
                    <span class="block text-lg font-extrabold text-slate-900 leading-none tracking-tight"><?php echo htmlspecialchars($app_name); ?></span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Academic System</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <a href="https://joshz-090.online" target="_blank" class="hidden md:inline-flex text-sm font-bold text-slate-500 hover:text-[var(--brand-color)] transition-colors">
                    Developer Contact
                </a>
                <a href="public/login.php" class="inline-flex items-center justify-center px-6 py-2.5 text-white text-sm font-bold rounded-full transition-all hover:scale-105 active:scale-95" style="background-color: var(--brand-color); box-shadow: 0 10px 15px -3px rgba(<?php echo $brand_rgb; ?>, 0.25);">
                    Secure Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <main class="pt-32 pb-20 px-6">
        <div class="max-w-4xl mx-auto text-center relative z-10">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-slate-50 border border-slate-100 rounded-full mb-8">
                <span class="w-2 h-2 rounded-full animate-pulse" style="background-color: var(--brand-color);"></span>
                <span class="text-xs font-bold uppercase tracking-widest" style="color: var(--brand-color);">Official Portal</span>
            </div>
            
            <h1 class="text-5xl md:text-7xl font-black text-slate-900 tracking-tight leading-[1.1] mb-8">
                The Bridge Between <br/>
                <span class="text-transparent bg-clip-text bg-gradient-to-r" style="background-image: linear-gradient(to right, var(--brand-color), #4f46e5);">Home & School</span>
            </h1>

            <p class="text-lg md:text-xl font-medium text-slate-500 max-w-2xl mx-auto leading-relaxed mb-12">
                <?php echo htmlspecialchars($footer_text); ?>
            </p>

            <div class="flex flex-col md:flex-row items-center justify-center gap-6">
                <a href="public/login.php" class="w-full md:w-auto px-8 py-4 bg-slate-900 text-white font-bold rounded-2xl hover:bg-slate-800 transition-all shadow-xl hover:-translate-y-1 flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Access Portal
                </a>
                <a href="https://joshz-090.online" target="_blank" class="w-full md:w-auto px-8 py-4 bg-white text-slate-900 font-bold rounded-2xl border-2 border-slate-100 hover:border-blue-100 hover:bg-blue-50 transition-all flex items-center justify-center gap-3">
                    <span>Technical Support</span>
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                </a>
            </div>
        </div>

        <!-- System Stats / Features -->
        <div class="max-w-5xl mx-auto mt-24 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-100 hover:border-blue-200 transition-colors">
                <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Academic Tracer</h3>
                <p class="text-sm font-medium text-slate-500">Real-time tracking of grades, assignments, and exam performance throughout the term.</p>
            </div>
            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-100 hover:border-purple-200 transition-colors">
                <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Student Insights</h3>
                <p class="text-sm font-medium text-slate-500">Comprehensive behavioral logs and attendance records directly from faculty.</p>
            </div>
            <div class="bg-white p-8 rounded-[32px] shadow-sm border border-slate-100 hover:border-emerald-200 transition-colors">
                <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center mb-6">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </div>
                <h3 class="text-xl font-bold text-slate-900 mb-2">Secure Access</h3>
                <p class="text-sm font-medium text-slate-500">Enterprise-grade security ensuring your child's data remains private and protected.</p>
            </div>
        </div>
    </main>

    <footer class="border-t border-slate-200 bg-white py-12">
        <div class="max-w-7xl mx-auto px-6 text-center">
            <p class="text-sm font-bold text-slate-400 mb-4">
                Designed & Developed by <a href="https://joshz-090.online" target="_blank" class="text-blue-600 hover:underline">Joshz-090</a>
            </p>
            <p class="text-xs font-semibold text-slate-300">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($app_name); ?>. All rights reserved.
            </p>
        </div>
    </footer>

</body>
</html>
