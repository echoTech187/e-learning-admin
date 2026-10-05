<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="auth-form-inner">
    <div class="form-header">
        <h1 class="form-title">Portal Manajemen EduNusa</h1>
        <p class="form-subtitle">Silakan masuk untuk mengakses dasbor administratif.</p>
    </div>

    <!-- Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert-custom alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-custom alert-danger-custom">
            <i class="fas fa-exclamation-circle"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('errors')): ?>
        <div class="alert-custom alert-danger-custom">
            <i class="fas fa-exclamation-triangle"></i>
            <ul class="mb-0 ps-3">
                <?php foreach (session()->getFlashdata('errors') as $err): ?>
                    <li><?= esc($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('/masuk') ?>" method="POST" id="formLogin" novalidate>
        <?= csrf_field() ?>

        <div class="form-group-custom">
            <label for="email" class="form-label-custom">Alamat Email</label>
            <div class="input-wrapper">
                <i class="fas fa-envelope input-icon"></i>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-input-custom"
                    placeholder="contoh@email.com"
                    value="<?= esc(old('email')) ?>"
                    required
                    autocomplete="email">
            </div>
        </div>

        <div class="form-group-custom">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <label for="password" class="form-label-custom mb-0">Password</label>
                <a href="<?= base_url('/lupa-password') ?>" class="forgot-link">Lupa Password?</a>
            </div>
            <div class="input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input-custom"
                    placeholder="Masukkan password Anda"
                    required
                    autocomplete="current-password">
                <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <div class="form-check-custom mb-3">
            <input type="checkbox" id="remember" name="remember" class="form-check-input-custom">
            <label for="remember" class="form-check-label-custom">Ingat saya selama 7 hari</label>
        </div>

        <button type="submit" class="btn-submit" id="btnLogin">
            <span class="btn-text">Masuk Sekarang</span>
            <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </form>

    <div class="alert alert-info mt-4" style="background-color: rgba(13,202,240,0.1); border-color: #0dcaf0; color: #055160; border-radius: 0.5rem; padding: 1rem;">
        <i class="fas fa-info-circle me-2"></i> <strong>Informasi:</strong> Pendaftaran akun Admin hanya dapat dilakukan secara internal oleh Super Admin atau Manajemen Pusat.
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function togglePassword(id, btn) {
    const input = document.getElementById(id);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

document.getElementById('formLogin').addEventListener('submit', function(e) {
    const btn = document.getElementById('btnLogin');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';
    btn.disabled = true;
});
</script>
<?= $this->endSection() ?>

