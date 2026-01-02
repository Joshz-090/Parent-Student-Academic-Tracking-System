<?php
/**
 * School Branding & Setup - Redesign
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    $school_name = sanitizeInput($_POST['school_name'] ?? '');
    $primary_color = sanitizeInput($_POST['primary_color'] ?? '#3B82F6');
    $footer_text = sanitizeInput($_POST['footer_text'] ?? '');
    $academic_year = sanitizeInput($_POST['academic_year'] ?? '');
    
    $logo_path = $branding['logo'];
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $upload_result = uploadFile($_FILES['logo'], 'logos');
        if ($upload_result['success']) {
            $logo_path = $upload_result['path'];
        } else {
            $error = $upload_result['message'];
        }
    }
    
    if (empty($error)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE schools 
                SET school_name = ?, logo = ?, primary_color = ?, footer_text = ?, academic_year = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$school_name, $logo_path, $primary_color, $footer_text, $academic_year, $school_id])) {
                $success = 'Branding profiles updated successfully!';
                $branding = getSchoolBranding($school_id); // Refresh branding
            } else {
                $error = 'Failed to commit changes to database';
            }
        } catch (PDOException $e) {
            error_log("Settings Update Error: " . $e->getMessage());
            $error = 'A system error occurred during persistence';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Branding - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
    <style>
        :root {
            --brand-color: <?php echo $branding['primary_color'] ?? '#3B82F6'; ?>;
            --brand-color-rgb: <?php echo hexToRgb($branding['primary_color'] ?? '#3B82F6'); ?>;
            --brand-text-color: <?php echo getContrastColor($branding['primary_color'] ?? '#3B82F6'); ?>;
        }
    </style>
    <script>
        // Set dynamic text color based on background luminance
        function updateTextContrast(hex) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
            const textColor = luminance > 0.7 ? '#1f2937' : '#ffffff';
            document.documentElement.style.setProperty('--brand-text-color', textColor);
            
            // Show warning if color is too light
            const warningEl = document.getElementById('colorWarning');
            if (warningEl) {
                warningEl.style.display = luminance > 0.9 ? 'block' : 'none';
            }
        }
        
        // Initialize contrast on load
        window.addEventListener('DOMContentLoaded', () => {
            updateTextContrast('<?php echo $branding['primary_color'] ?? '#3B82F6'; ?>');
        });
    </script>
</head>
<body class="bg-[#f8fafc]">
    
    <?php include __DIR__ . '/../../includes/partials/sidebar_admin.php'; ?>

    <!-- Main Content -->
    <main class="ml-0 md:ml-[280px] min-h-screen">
        <!-- Top Navigation -->
        <header class="h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-8 sticky top-0 z-40">
            <h1 class="text-xl font-extrabold text-gray-900 tracking-tight underline decoration-4 decoration-[var(--brand-color)] underline-offset-8">Configure Boarding</h1>
            
            <button onclick="toggleSidebar()" class="md:hidden p-3 bg-gray-50 rounded-2xl hover:bg-gray-100 transition-colors">
                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </header>

        <div class="p-8 max-w-4xl mx-auto">
            <div class="mb-10 text-center md:text-left">
                <h2 class="text-3xl font-black text-gray-900 mb-2">School Identity</h2>
                <p class="text-gray-500 font-medium">Customize how your institution appears to faculty and parents.</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-5 mb-8 rounded-2xl shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <p class="font-bold"><?php echo htmlspecialchars($success); ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="bg-white rounded-[32px] border border-gray-100 shadow-sm p-10">
                <div class="space-y-8">
                    <!-- Branding Section -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Institution Name</label>
                            <input 
                                type="text" 
                                name="school_name" 
                                required
                                value="<?php echo htmlspecialchars($branding['school_name']); ?>"
                                class="w-full px-6 py-4 bg-gray-50 border-transparent focus:bg-white focus:ring-4 focus:ring-[var(--brand-color)]/10 focus:border-[var(--brand-color)] rounded-2xl font-bold text-gray-900 transition-all outline-none"
                            >
                        </div>
                        <div>
                            <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-3">Academic Term</label>
                            <input 
                                type="text" 
                                name="academic_year" 
                                placeholder="e.g. 2024-2025"
                                value="<?php echo htmlspecialchars($branding['academic_year']); ?>"
                                class="w-full px-6 py-4 bg-gray-50 border-transparent focus:bg-white focus:ring-4 focus:ring-[var(--brand-color)]/10 focus:border-[var(--brand-color)] rounded-2xl font-bold text-gray-900 transition-all outline-none"
                            >
                        </div>
                    </div>

                    <!-- Visual Section -->
                    <div class="pt-8 border-t border-gray-50">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-6">Master Logo</label>
                                <div class="flex items-center gap-6">
                                    <div class="w-24 h-24 rounded-[28px] bg-gray-100 flex items-center justify-center overflow-hidden border-4 border-white shadow-lg shrink-0">
                                        <?php if ($branding['logo']): ?>
                                            <img id="logoPreview" src="<?php echo APP_URL; ?>/assets/uploads/<?php echo $branding['logo']; ?>" alt="Logo" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div id="logoPlaceholder" class="text-4xl font-black text-gray-300">?</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1">
                                        <input 
                                            type="file" 
                                            id="logoInput"
                                            name="logo" 
                                            accept="image/*"
                                            class="hidden"
                                            onchange="previewImage(this)"
                                        >
                                        <button type="button" onclick="document.getElementById('logoInput').click()" class="bg-gray-100 hover:bg-gray-200 text-gray-900 px-5 py-3 rounded-xl font-bold text-sm transition-colors w-full mb-2">Upload New</button>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase">SVG, PNG or JPG (Max 2MB)</p>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-6">Primary Theme Color</label>
                                <div class="flex items-center gap-4">
                                    <div class="relative w-24 h-14 rounded-2xl overflow-hidden shadow-inner group border border-gray-100">
                                        <input 
                                            type="color" 
                                            id="colorPicker"
                                            name="primary_color" 
                                            value="<?php echo htmlspecialchars($branding['primary_color']); ?>"
                                            oninput="updateColorHex(this.value)"
                                            class="absolute -inset-2 w-[150%] h-[150%] cursor-pointer"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <input 
                                            type="text" 
                                            id="colorHex"
                                            readonly
                                            value="<?php echo htmlspecialchars($branding['primary_color']); ?>"
                                            class="w-full bg-gray-50 border-none rounded-xl px-4 py-3 font-mono font-bold text-gray-500 outline-none"
                                        >
                                    </div>
                                </div>
                                <p id="colorWarning" class="text-[10px] font-bold text-amber-500 uppercase mt-2 hidden">
                                    <svg class="w-3 h-3 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    Very light colors may affect visibility
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Persistence -->
                    <div class="pt-8 border-t border-gray-50">
                        <label class="block text-xs font-black text-gray-400 uppercase tracking-[0.2em] mb-4">Portal Disclaimer / Footer</label>
                        <textarea 
                            name="footer_text" 
                            rows="4"
                            class="w-full px-6 py-4 bg-gray-50 border-transparent focus:bg-white focus:ring-4 focus:ring-[var(--brand-color)]/10 focus:border-[var(--brand-color)] rounded-2xl font-bold text-gray-900 transition-all outline-none resize-none"
                            placeholder="Shared footer text for all portals..."
                        ><?php echo htmlspecialchars($branding['footer_text']); ?></textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-10 flex flex-col md:flex-row gap-4">
                        <button 
                            type="submit" 
                            name="update_settings"
                            class="flex-1 bg-[var(--brand-color)] shadow-lg shadow-[var(--brand-color)]/20 hover:scale-[1.02] active:scale-[0.98] text-[var(--brand-text-color)] font-black py-5 rounded-2xl transition-all duration-300"
                        >
                            Sync Branding Profile
                        </button>
                        <a 
                            href="dashboard.php"
                            class="md:w-32 bg-gray-100 hover:bg-gray-200 text-gray-500 font-bold py-5 rounded-2xl text-center transition-colors"
                        >
                            Revert
                        </a>
                    </div>
                </div>
            </form>

            <!-- Danger Zone -->
            <div class="mt-16 pt-10 border-t-4 border-dashed border-red-50">
                <div class="bg-red-50 rounded-[32px] p-10 border border-red-100 shadow-sm overflow-hidden relative group">
                    <div class="relative z-10">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-12 h-12 bg-red-100 rounded-2xl flex items-center justify-center text-red-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-red-900">Danger Zone</h3>
                                <p class="text-xs font-bold text-red-400 uppercase tracking-widest mt-1">Institutional Master Reset</p>
                            </div>
                        </div>
                        
                        <p class="text-sm font-bold text-red-700/60 max-w-2xl leading-relaxed mb-8">
                            Performing a Master Reset will permanently erase all students, marks, attendance records, and faculty accounts. This action is irreversible. Administrative login credentials will be preserved.
                        </p>

                        <button 
                            onclick="confirmMasterReset()"
                            class="bg-red-600 hover:bg-red-700 text-white font-black px-8 py-4 rounded-2xl shadow-xl shadow-red-200 transition-all active:scale-95 flex items-center gap-3"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Execute Master Reset
                        </button>
                    </div>
                    
                    <!-- Decorative BG icon -->
                    <svg class="absolute -right-10 -bottom-10 w-64 h-64 text-red-100/50 transform rotate-12 group-hover:rotate-0 transition-transform duration-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                </div>
            </div>
        </div>
    </main>

    <script src="<?php echo APP_URL; ?>/assets/js/toast.js"></script>
    <script src="<?php echo APP_URL; ?>/assets/js/main.js"></script>
    <script>
        function confirmMasterReset() {
            const firstConfirmation = confirm("CRITICAL WARNING: This will permanently delete ALL transactional data (Students, Grades, Attendance). Only Admin accounts will remain.\n\nAre you absolutely sure?");
            if (firstConfirmation) {
                const secondConfirmation = confirm("FINAL WARNING: This action is irreversible. Proceed with system-wide data erasure?");
                if (secondConfirmation) {
                    window.location.href = "../../database/clear_data.php?web_trigger=true";
                }
            }
        }
        function updateColorHex(val) {
            document.getElementById('colorHex').value = val.toUpperCase();
            document.documentElement.style.setProperty('--brand-color', val);
            updateTextContrast(val);
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logoPreview');
                    const placeholder = document.getElementById('logoPlaceholder');
                    
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        // Create preview if it doesn't exist
                        const container = placeholder.parentElement;
                        const img = document.createElement('img');
                        img.id = 'logoPreview';
                        img.src = e.target.result;
                        img.className = 'w-full h-full object-cover';
                        container.innerHTML = '';
                        container.appendChild(img);
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        <?php if ($success): ?>
            showToast('<?php echo $success; ?>', 'success');
        <?php endif; ?>
        <?php if ($error): ?>
            showToast('<?php echo $error; ?>', 'error');
        <?php endif; ?>
    </script>
</body>
</html>
