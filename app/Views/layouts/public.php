<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'EduNusa — Platform E-Learning Terbaik Indonesia') ?></title>
    <meta name="description" content="<?= esc($meta_desc ?? 'Belajar dari instruktur terbaik dengan ribuan kursus online berkualitas tinggi.') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>?v=<?= time() ?>">
    <?= $this->renderSection('head') ?>
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar-edu" id="mainNav">
    <div class="container">
        <a href="<?= base_url('/') ?>" class="navbar-brand-edu">
            <div class="brand-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <span>EduNusa</span>
        </a>

        <div class="navbar-overlay" id="navOverlay"></div>
        <div class="navbar-menu" id="navMenu">
            <div class="navbar-menu-header">
                <a href="<?= base_url('/') ?>" class="navbar-brand-edu m-0">
                    <div class="brand-icon brand-icon-sm"><i class="fas fa-graduation-cap"></i></div>
                    <span>EduNusa</span>
                </a>
                <button class="btn-close-menu" id="navClose"><i class="fas fa-times"></i></button>
            </div>

            <div class="navbar-nav-links">
                <a href="<?= base_url('/') ?>" class="nav-link-edu <?= uri_string() === '' ? 'active' : '' ?>">Beranda</a>
                <a href="<?= base_url('/kursus') ?>" class="nav-link-edu <?= str_starts_with(uri_string(), 'kursus') ? 'active' : '' ?>">Kursus</a>
                <a href="<?= base_url('/tentang-kami') ?>" class="nav-link-edu <?= uri_string() === 'tentang-kami' ? 'active' : '' ?>">Tentang Kami</a>
                <a href="<?= base_url('/kontak') ?>" class="nav-link-edu <?= uri_string() === 'kontak' ? 'active' : '' ?>">Kontak</a>
            </div>

            <div class="navbar-actions">
            <?php if (session()->get('is_logged_in')): ?>
                <?php
                    $role = session()->get('user_role');
                    $dashboard = match($role) {
                        'admin'      => '/admin',
                        'instructor' => '/instruktur',
                        'student'    => '/siswa',
                        'parent'     => '/orang-tua',
                        default      => '/',
                    };
                ?>
                <a href="<?= base_url($dashboard) ?>" class="btn-nav-primary">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            <?php else: ?>
                <a href="<?= base_url('/masuk') ?>" class="btn-nav-outline">Masuk</a>
                <a href="<?= base_url('/daftar') ?>" class="btn-nav-primary">Daftar Gratis</a>
            <?php endif; ?>
            </div>
        </div>

        <button class="navbar-toggler-edu" id="navToggler">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<!-- Page Content -->
<?= $this->renderSection('content') ?>

<!-- ===== FOOTER ===== -->
<footer class="footer-edu">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="<?= base_url('/') ?>" class="footer-logo">
                    <div class="brand-icon brand-icon-sm">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <span>EduNusa</span>
                </a>
                <p>Platform e-learning terpercaya untuk generasi Indonesia yang ingin terus berkembang dan berprestasi.</p>
                <div class="footer-socials">
                    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    <a href="#" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Platform</h4>
                <ul>
                    <li><a href="<?= base_url('/kursus') ?>">Semua Kursus</a></li>
                    <li><a href="<?= base_url('/daftar') ?>">Daftar Gratis</a></li>
                    <li><a href="<?= base_url('/tentang-kami') ?>">Tentang Kami</a></li>
                    <li><a href="#">Menjadi Instruktur</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Bantuan</h4>
                <ul>
                    <li><a href="<?= base_url('/kontak') ?>">Hubungi Kami</a></li>
                    <li><a href="#">FAQ</a></li>
                    <li><a href="#">Kebijakan Privasi</a></li>
                    <li><a href="#">Syarat & Ketentuan</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Kontak</h4>
                <ul>
                    <li><i class="fas fa-envelope me-2"></i>info@edunusa.id</li>
                    <li><i class="fas fa-phone me-2"></i>+62 811-2345-6789</li>
                    <li><i class="fas fa-map-marker-alt me-2"></i>Jakarta, Indonesia</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?= date('Y') ?> EduNusa. Hak cipta dilindungi.</p>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= base_url('assets/js/main.js') ?>"></script>
<script>
// Navbar scroll effect
window.addEventListener('scroll', function() {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 20) {
        nav.classList.add('scrolled');
    } else {
        nav.classList.remove('scrolled');
    }
});

// Mobile nav toggle
const navMenu = document.getElementById('navMenu');
const navOverlay = document.getElementById('navOverlay');

function toggleMenu() {
    navMenu.classList.toggle('open');
    navOverlay.classList.toggle('open');
    document.body.classList.toggle('menu-open');
}

document.getElementById('navToggler').addEventListener('click', toggleMenu);
document.getElementById('navClose').addEventListener('click', toggleMenu);
navOverlay.addEventListener('click', toggleMenu);
</script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
