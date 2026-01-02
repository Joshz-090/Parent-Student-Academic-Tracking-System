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
                    <div class="w-12 h-12 rounded-2xl bg-[var(--brand-color)] flex items-center justify-center text-[var(--brand-text-color)] text-xl font-bold shadow-sm">
                        <?php echo substr($branding['school_name'] ?? 'S', 0, 1); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="overflow-hidden">
                <h2 class="font-extrabold text-gray-900 truncate"><?php echo htmlspecialchars($branding['school_name']); ?></h2>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest">Guardian Portal</p>
            </div>
        </div>
    </div>
    
    <nav class="flex-1 px-4 space-y-1">
        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            <span class="font-semibold">My Household</span>
        </a>
        
        <a href="tracer.php" class="nav-link <?php echo $current_page === 'tracer.php' ? 'active' : ''; ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
            <span class="font-semibold">Timeline Tracer</span>
        </a>
    </nav>
    
    <div class="p-6 border-t border-gray-50">
        <a href="<?php echo APP_URL; ?>/logout.php" class="flex items-center gap-3 px-4 py-4 text-red-500 hover:bg-red-50 rounded-2xl transition-all duration-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
            <span class="font-bold">Sign Out</span>
        </a>
    </div>
</aside>
