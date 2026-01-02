<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="sidebar flex flex-col">
    <div class="p-8 border-b border-gray-50 mb-6">
        <div class="flex items-center gap-4">
            <div class="shrink-0">
                <?php if (!empty($branding['logo'])): ?>
                    <img src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $branding['logo']; ?>" alt="School Logo" class="w-12 h-12 rounded-2xl object-cover shadow-sm">
                <?php else: ?>
                    <div class="w-12 h-12 rounded-2xl bg-[var(--brand-color)] flex items-center justify-center text-[var(--brand-text-color)] text-xl font-bold shadow-sm transition-colors duration-300">
                        <?php echo substr($branding['school_name'] ?? 'S', 0, 1); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden">
                <h2 class="font-extrabold text-gray-900 truncate"><?php echo htmlspecialchars($branding['school_name']); ?></h2>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Administrator</p>
            </div>
        </div>
    </div>
    
    <nav class="flex-1 px-4 space-y-1">
        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span class="font-semibold">General Overview</span>
        </a>
        
        <a href="school_settings.php" class="nav-link <?php echo $current_page === 'school_settings.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path></svg>
            <span class="font-semibold">Branding & Setup</span>
        </a>
        
        <div class="pt-4 pb-2 px-4 text-[10px] font-bold text-gray-400 uppercase tracking-[0.2em]">Management</div>
        
        <a href="manage_users.php" class="nav-link <?php echo $current_page === 'manage_users.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            <span class="font-semibold">Staff & User Directory</span>
        </a>
        
        <a href="manage_students.php" class="nav-link <?php echo $current_page === 'manage_students.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path></svg>
            <span class="font-semibold">Student Database</span>
        </a>
        
        <a href="manage_classes.php" class="nav-link <?php echo $current_page === 'manage_classes.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2"></path></svg>
            <span class="font-semibold">Class Scheduling</span>
        </a>
    </nav>
    
    <div class="p-6 border-t border-gray-50">
        <a href="<?php echo APP_URL; ?>/logout.php" class="flex items-center gap-3 px-4 py-4 text-red-500 hover:bg-red-50 rounded-2xl transition-all duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span class="font-bold">Sign Out</span>
        </a>
    </div>
</aside>
