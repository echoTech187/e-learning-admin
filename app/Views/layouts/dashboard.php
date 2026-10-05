<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Dashboard') ?> &mdash; EduNusa</title>
    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('favicon.ico') ?>" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Bootstrap 5 --><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"><!-- CSS (Tailwind Compiled & Custom) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
        <style>
        :root {
            --primary: #000000;
            --primary-light: #f3f4f6;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --sidebar-width: 260px;
            --bg-body: #ffffff;
        }
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        /* Sidebar - Ultra Minimal */
        .sidebar {
            width: var(--sidebar-width);
            background: #ffffff;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            border-right: 1px solid var(--border-color);
            z-index: 1040;
            overflow-y: auto;
            transition: all 0.3s ease;
        }
        
        .sidebar-brand {
            height: 72px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            text-decoration: none;
            border-bottom: 1px solid var(--border-color);
            letter-spacing: -0.02em;
        }
        
        .sidebar-brand .icon-box {
            width: 32px;
            height: 32px;
            background: #000;
            color: #fff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 0.9rem;
        }
        
        .sidebar-nav {
            padding: 24px 16px;
        }
        
        .nav-label {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #9ca3af;
            margin-bottom: 12px;
            margin-left: 12px;
            margin-top: 24px;
        }
        
        .nav-item {
            margin-bottom: 4px;
        }
        
        .nav-link-dash {
            display: flex;
            align-items: center;
            padding: 10px 12px;
            color: var(--text-muted);
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .nav-link-dash i {
            width: 24px;
            font-size: 1.1rem;
            text-align: center;
            margin-right: 12px;
        }
        
        .nav-link-dash:hover {
            background: #f3f4f6;
            color: var(--text-dark);
        }
        
        .nav-link-dash.active {
            background: #000000;
            color: #ffffff;
        }
        .nav-link-dash.active i {
            color: #ffffff;
        }

        /* Topbar - Flat */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .topbar {
            height: 72px;
            background: #ffffff;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        
        .topbar-title {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--text-dark);
            letter-spacing: -0.02em;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 20px;
            transition: background 0.2s;
        }
        .user-profile:hover {
            background: #f3f4f6;
        }
        .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.9rem;
        }
        
        .user-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-dark);
        }
        
        .user-role {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* Reset content area padding if injected by view */
        .content-area-wrapper {
            padding: 24px;
            flex: 1;
        }

        /* Badge adjustments */
        .badge.bg-danger {
            background-color: #ef4444 !important;
            font-size: 0.7rem;
            padding: 4px 8px;
        }
            /* Restored Missing Classes for Topbar Layout */
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }
        /* Custom Scrollbar for Sidebar */
        .sidebar::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #e5e7eb;
            border-radius: 4px;
        }
            /* RESTORE PADDING FOR CONTENT AREA */
        .content-area {
            padding: 24px;
            flex: 1;
            margin: 0 auto;
            width: 100%;
        }

        /* FIX TOPBAR PROFILE */
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            padding: 6px 12px;
            border-radius: 20px;
            transition: background 0.2s;
        }
        .user-profile:hover {
            background: #f3f4f6;
        }
        .dropdown-menu.show {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .user-info {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            text-align: left;
        }
        .user-name {
            font-size: 0.85rem;
            font-weight: 700;
            color: #000;
        }
        .user-role {
            font-size: 0.65rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        /* FIX NOTIFICATION ICON */
        .notification-icon {
            position: relative;
            color: #6b7280;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .notification-icon:hover {
            background: #f3f4f6;
        }
        .notification-icon i {
            font-size: 1.2rem;
        }
        .notification-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            background: #ef4444;
            color: white;
            font-size: 0.6rem;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 10px;
            border: 2px solid #fff;
        }
        /* AgyConfirm Modal - Centered and Neater */
    .agy-confirm-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 10000; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; pointer-events: none; }
    .agy-confirm-overlay.show { opacity: 1; pointer-events: auto; }
    .agy-confirm-box { background: white; padding: 36px 32px; border-radius: 24px; width: 100%; max-width: 380px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center; }
    .agy-confirm-overlay.show .agy-confirm-box { transform: translateY(0) scale(1); }
    .agy-confirm-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 20px auto; }
    .agy-confirm-icon.danger { background: #fee2e2; color: #dc2626; }
    .agy-confirm-icon.warning { background: #fef08a; color: #d97706; }
    .agy-confirm-icon.info { background: #dbeafe; color: #2563eb; }
    .agy-confirm-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; letter-spacing: -0.02em; }
    .agy-confirm-message { color: #64748b; font-size: 0.95rem; margin-bottom: 32px; line-height: 1.6; padding: 0 10px; }
    .agy-confirm-actions { display: flex; gap: 12px; justify-content: center; }
    .agy-confirm-btn { padding: 12px 0; flex: 1; border-radius: 12px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; cursor: pointer; border: none; transition: all 0.2s; }
    .agy-confirm-cancel { background: #f1f5f9; color: #475569; }
    .agy-confirm-cancel:hover { background: #e2e8f0; color: #0f172a; }
    .agy-confirm-confirm.danger { background: #ef4444; color: white; }
    .agy-confirm-confirm.danger:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); }
    .agy-confirm-confirm.warning { background: #f59e0b; color: white; }
    .agy-confirm-confirm.warning:hover { background: #d97706; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2); }
    .agy-confirm-confirm.info { background: #3b82f6; color: white; }
    .agy-confirm-confirm.info:hover { background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
</style>
    <?php if(session()->getFlashdata('success')): ?>
    <meta name="flash-success" content="<?= esc(session()->getFlashdata('success')) ?>">
    <?php endif; ?>
    <?php if(session()->getFlashdata('error')): ?>
    <meta name="flash-error" content="<?= esc(session()->getFlashdata('error')) ?>">
    <?php endif; ?>
</head>
<body>
<?php $uri = current_url(true)->getPath(); ?>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="<?= base_url() ?>" class="sidebar-brand">
                <div class="icon-box"><i class="fas fa-graduation-cap"></i></div>
                <span>EduNusa</span>
            </a>
        </div>
        <?php helper('sidebar'); ?>
<?php $role = session()->get('user_role') ?? 'superadmin'; ?>
<div class="sidebar-nav">
            
<div class="nav-label mt-2">UTAMA</div>
<div class="nav-item">
    <a href="<?= base_url('admin') ?>" class="nav-link-dash <?= ($uri == 'admin' || $uri == '') ? 'active' : '' ?>">
        <i class="fas fa-chart-pie"></i> Ringkasan
    </a>
</div>
<?php if(in_array($role, ['superadmin', 'content_manager'])): ?>
<div class="nav-item">
    <a href="<?= base_url('admin/persetujuan') ?>" class="nav-link-dash d-flex justify-content-between align-items-center <?= ($uri == 'admin/persetujuan') ? 'active' : '' ?>">
        <span><i class="fas fa-check-circle"></i> Persetujuan</span>
        <?php $p_count = get_sidebar_badge('persetujuan'); if($p_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $p_count ?></span><?php endif; ?>
    </a>
</div>
<?php endif; ?>

<?php if(in_array($role, ['superadmin'])): ?>
<div class="nav-label mt-4">PENGGUNA</div>
<div class="nav-item">
    <a href="<?= base_url('admin/pengguna') ?>" class="nav-link-dash <?= ($uri == 'admin/pengguna') ? 'active' : '' ?>">
        <i class="fas fa-user-graduate"></i> Murid & Orang Tua
    </a>
</div>
<div class="nav-item">
    <a href="<?= base_url('admin/mentor') ?>" class="nav-link-dash <?= ($uri == 'admin/mentor') ? 'active' : '' ?>">
        <i class="fas fa-chalkboard-teacher"></i> Mentor/Pengajar
    </a>
</div>
<?php endif; ?>

<?php if(in_array($role, ['superadmin', 'content_manager'])): ?>
<div class="nav-label mt-4">OPERASIONAL</div>
<div class="nav-item">
    <a href="<?= base_url('admin/program') ?>" class="nav-link-dash <?= ($uri == 'admin/program') ? 'active' : '' ?>">
        <i class="fas fa-book-open"></i> Program & Kelas
    </a>
</div>
<div class="nav-item">
    <a href="<?= base_url('admin/booking') ?>" class="nav-link-dash <?= ($uri == 'admin/booking') ? 'active' : '' ?>">
        <i class="fas fa-calendar-alt"></i> Booking & Jadwal
    </a>
</div>
<?php endif; ?>

<?php if(in_array($role, ['superadmin', 'finance'])): ?>
<div class="nav-label mt-4">KEUANGAN</div>
<div class="nav-item">
    <a href="<?= base_url('transaksi') ?>" class="nav-link-dash <?= ($uri == 'transaksi') ? 'active' : '' ?>">
        <i class="fas fa-wallet"></i> Transaksi
    </a>
</div>
<div class="nav-item">
    <a href="<?= base_url('admin/payout') ?>" class="nav-link-dash <?= ($uri == 'admin/payout') ? 'active' : '' ?>">
        <i class="fas fa-money-bill-wave"></i> Payout Mentor
    </a>
</div>
<?php endif; ?>

<div class="nav-label mt-4">LAINNYA</div>
<?php if(in_array($role, ['superadmin', 'finance'])): ?>
<div class="nav-item">
    <a href="<?= base_url('report') ?>" class="nav-link-dash <?= ($uri == 'report') ? 'active' : '' ?>">
        <i class="fas fa-file-alt"></i> Laporan
    </a>
</div>
<?php endif; ?>
<?php if(in_array($role, ['superadmin', 'content_manager'])): ?>
<div class="nav-item">
    <a href="<?= base_url('admin/konten') ?>" class="nav-link-dash <?= ($uri == 'admin/konten') ? 'active' : '' ?>">
        <i class="fas fa-globe"></i> Konten Website
    </a>
</div>
<?php endif; ?>
<?php if(in_array($role, ['superadmin'])): ?>
<div class="nav-item">
    <a href="<?= base_url('admin/pengaturan') ?>" class="nav-link-dash <?= ($uri == 'admin/pengaturan') ? 'active' : '' ?>">
        <i class="fas fa-cog"></i> Pengaturan
    </a>
</div>
<?php endif; ?>
        </div>

        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Topbar -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" id="btnToggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0 topbar-title d-none d-md-block"><?= esc($title ?? 'Dashboard') ?></h4>
            </div>
            
            <div class="topbar-right">
                <a href="#" class="notification-icon">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </a>
                
                <div class="dropdown">
                    <a class="user-profile dropdown-toggle text-decoration-none" href="#" role="button" aria-expanded="false" style="cursor: pointer; color: inherit;">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info d-none d-md-flex">
                            <span class="user-name"><?= esc(session()->get('user_name') ?? 'Pengguna') ?></span>
                            <span class="user-role"><?= esc(session()->get('user_role') ?? 'Role') ?></span>
                        </div>
                    </a><ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm mt-2">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user-circle me-2 text-muted"></i> Profil Saya</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2 text-muted"></i> Pengaturan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= base_url('/keluar') ?>"><i class="fas fa-sign-out-alt me-2"></i> Keluar</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <div class="content-area">
            <?= $this->renderSection('content') ?>
        </div>
    </main>

    <!-- Bootstrap Bundle (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('btnToggleSidebar')?.addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('show');
        });
        // Manual Dropdown Fallback
        document.addEventListener('click', function(e) {
            const toggle = e.target.closest('.user-profile.dropdown-toggle');
            const dropdown = toggle ? toggle.nextElementSibling : null;
            
            if (toggle && dropdown && dropdown.classList.contains('dropdown-menu')) {
                e.preventDefault();
                dropdown.classList.toggle('show');
            } else if (!e.target.closest('.dropdown-menu') && !e.target.closest('.user-profile')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(el => el.classList.remove('show'));
            }
        });
    </script>
    <?= $this->renderSection('scripts') ?>
    <style>
    /* Custom Toast - Premium B2B Style */
    #agy-toast-container { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 12px; pointer-events: none; }
    .agy-toast { background: #fff; color: #0f172a; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); padding: 16px 20px; min-width: 300px; max-width: 400px; display: flex; align-items: flex-start; gap: 12px; transform: translateX(120%); opacity: 0; transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); border: 1px solid #f1f5f9; pointer-events: auto; font-family: 'Plus Jakarta Sans', sans-serif; position: relative; overflow: hidden; }
    .agy-toast.show { transform: translateX(0); opacity: 1; }
    .agy-toast.hide { transform: translateX(120%); opacity: 0; }
    .agy-toast-icon { width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; border-radius: 50%; flex-shrink: 0; font-size: 12px; }
    .agy-toast.success .agy-toast-icon { background: #dcfce7; color: #16a34a; }
    .agy-toast.error .agy-toast-icon { background: #fee2e2; color: #ef4444; }
    .agy-toast.info .agy-toast-icon { background: #e0f2fe; color: #0284c7; }
    .agy-toast.warning .agy-toast-icon { background: #fef9c3; color: #ca8a04; }
    .agy-toast-content { flex: 1; display: flex; flex-direction: column; gap: 4px; }
    .agy-toast-title { font-weight: 700; font-size: 0.95rem; line-height: 1.2; }
    .agy-toast-message { font-size: 0.85rem; color: #64748b; line-height: 1.4; }
    .agy-toast-close { background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 0; font-size: 14px; transition: color 0.2s; }
    .agy-toast-close:hover { color: #64748b; }
    .agy-toast-progress { position: absolute; bottom: 0; left: 0; height: 3px; background: #f1f5f9; width: 100%; }
    .agy-toast-progress-bar { height: 100%; width: 100%; transform-origin: left; }
    .agy-toast.success .agy-toast-progress-bar { background: #22c55e; }
    .agy-toast.error .agy-toast-progress-bar { background: #ef4444; }
    .agy-toast.info .agy-toast-progress-bar { background: #3b82f6; }
    .agy-toast.warning .agy-toast-progress-bar { background: #eab308; }
        /* AgyConfirm Modal - Centered and Neater */
    .agy-confirm-overlay { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 10000; display: flex; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s; pointer-events: none; }
    .agy-confirm-overlay.show { opacity: 1; pointer-events: auto; }
    .agy-confirm-box { background: white; padding: 36px 32px; border-radius: 24px; width: 100%; max-width: 380px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: translateY(20px) scale(0.95); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); text-align: center; }
    .agy-confirm-overlay.show .agy-confirm-box { transform: translateY(0) scale(1); }
    .agy-confirm-icon { width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.75rem; margin: 0 auto 20px auto; }
    .agy-confirm-icon.danger { background: #fee2e2; color: #dc2626; }
    .agy-confirm-icon.warning { background: #fef08a; color: #d97706; }
    .agy-confirm-icon.info { background: #dbeafe; color: #2563eb; }
    .agy-confirm-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.35rem; font-weight: 800; color: #0f172a; margin-bottom: 12px; letter-spacing: -0.02em; }
    .agy-confirm-message { color: #64748b; font-size: 0.95rem; margin-bottom: 32px; line-height: 1.6; padding: 0 10px; }
    .agy-confirm-actions { display: flex; gap: 12px; justify-content: center; }
    .agy-confirm-btn { padding: 12px 0; flex: 1; border-radius: 12px; font-weight: 700; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; cursor: pointer; border: none; transition: all 0.2s; }
    .agy-confirm-cancel { background: #f1f5f9; color: #475569; }
    .agy-confirm-cancel:hover { background: #e2e8f0; color: #0f172a; }
    .agy-confirm-confirm.danger { background: #ef4444; color: white; }
    .agy-confirm-confirm.danger:hover { background: #dc2626; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); }
    .agy-confirm-confirm.warning { background: #f59e0b; color: white; }
    .agy-confirm-confirm.warning:hover { background: #d97706; box-shadow: 0 4px 12px rgba(217, 119, 6, 0.2); }
    .agy-confirm-confirm.info { background: #3b82f6; color: white; }
    .agy-confirm-confirm.info:hover { background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
</style>
    <div id="agy-toast-container"></div>
    <script>
    class AgyConfirm {
    static show(title, message, type = 'danger', confirmText = 'Confirm', cancelText = 'Cancel') {
        return new Promise((resolve) => {
            const overlay = document.createElement('div');
            overlay.className = 'agy-confirm-overlay';
            let icon = 'fa-exclamation-triangle';
            if (type === 'info') icon = 'fa-info-circle';
            
            overlay.innerHTML = `
                <div class="agy-confirm-box">
                    <div class="agy-confirm-icon ${type}"><i class="fas ${icon}"></i></div>
                    <div class="agy-confirm-title">${title}</div>
                    <div class="agy-confirm-message">${message}</div>
                    <div class="agy-confirm-actions">
                        <button class="agy-confirm-btn agy-confirm-cancel">${cancelText}</button>
                        <button class="agy-confirm-btn agy-confirm-confirm ${type}">${confirmText}</button>
                    </div>
                </div>
            `;
            document.body.appendChild(overlay);
            requestAnimationFrame(() => overlay.classList.add('show'));
            
            const cleanup = (result) => {
                overlay.classList.remove('show');
                setTimeout(() => {
                    if (overlay.parentNode) overlay.parentNode.removeChild(overlay);
                    resolve(result);
                }, 200);
            };
            
            overlay.querySelector('.agy-confirm-cancel').addEventListener('click', () => cleanup(false));
            overlay.querySelector('.agy-confirm-confirm').addEventListener('click', () => cleanup(true));
        });
    }
}

class AgyToast {
        static show(type, title, message = '', duration = 4000) {
            const container = document.getElementById('agy-toast-container');
            const toast = document.createElement('div');
            toast.className = `agy-toast ${type}`;
            
            let iconClass = 'fa-info';
            if (type === 'success') iconClass = 'fa-check';
            if (type === 'error') iconClass = 'fa-exclamation';
            if (type === 'warning') iconClass = 'fa-exclamation-triangle';
            
            toast.innerHTML = `
                <div class="agy-toast-icon"><i class="fas ${iconClass}"></i></div>
                <div class="agy-toast-content">
                    <div class="agy-toast-title">${title}</div>
                    ${message ? `<div class="agy-toast-message">${message}</div>` : ''}
                </div>
                <button class="agy-toast-close"><i class="fas fa-times"></i></button>
                <div class="agy-toast-progress"><div class="agy-toast-progress-bar"></div></div>
            `;
            
            container.appendChild(toast);
            
            // Trigger reflow
            toast.offsetHeight;
            toast.classList.add('show');
            
            // Animate progress bar
            const progressBar = toast.querySelector('.agy-toast-progress-bar');
            progressBar.style.transition = `transform ${duration}ms linear`;
            requestAnimationFrame(() => {
                progressBar.style.transform = 'scaleX(0)';
            });
            
            const removeToast = () => {
                toast.classList.remove('show');
                toast.classList.add('hide');
                setTimeout(() => toast.remove(), 300);
            };
            
            let timeout = setTimeout(removeToast, duration);
            
            toast.querySelector('.agy-toast-close').addEventListener('click', () => {
                clearTimeout(timeout);
                removeToast();
            });
        }
        
        static success(title, msg) { this.show('success', title, msg); }
        static error(title, msg) { this.show('error', title, msg); }
        static info(title, msg) { this.show('info', title, msg); }
        static warning(title, msg) { this.show('warning', title, msg); }
    }
    
    // Fix for Bootstrap 5 aria-hidden focus bug
document.addEventListener('hide.bs.modal', function (event) {
    if (document.activeElement) {
        document.activeElement.blur();
    }
});

// Automatically show flashdata if exists
    document.addEventListener('DOMContentLoaded', () => {
        const flashSuccess = document.querySelector('meta[name="flash-success"]')?.content;
        const flashError = document.querySelector('meta[name="flash-error"]')?.content;
        if (flashSuccess) AgyToast.success('Success', flashSuccess);
        if (flashError) AgyToast.error('Error', flashError);
    });
    </script>
</body>
</html>













