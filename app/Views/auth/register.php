<?= $this->extend('layouts/auth') ?>

<?= $this->section('content') ?>
<div class="auth-form-inner">
    <div class="form-header">
        <h1 class="form-title">Mulai Belajar Hari Ini!</h1>
        <p class="form-subtitle">Daftar gratis dan akses ratusan kursus berkualitas.</p>
    </div>

    <!-- Alerts -->
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
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert-custom alert-danger-custom">
            <i class="fas fa-exclamation-circle"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('/daftar') ?>" method="POST" id="formRegister" novalidate>
        <?= csrf_field() ?>

        <div class="form-group-custom">
            <label for="name" class="form-label-custom">Nama Lengkap</label>
            <div class="input-wrapper">
                <i class="fas fa-user input-icon"></i>
                <input
                    type="text"
                    id="name"
                    name="name"
                    class="form-input-custom"
                    placeholder="Nama lengkap Anda"
                    value="<?= esc(old('name')) ?>"
                    required>
            </div>
        </div>

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
                    required>
            </div>
        </div>

        <div class="form-group-custom">
            <label for="password" class="form-label-custom">Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input-custom"
                    placeholder="Minimal 8 karakter"
                    required>
                <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <!-- Password strength indicator -->
            <div class="password-strength mt-2" id="pwdStrength" style="display:none;">
                <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <small id="strengthText" class="strength-text"></small>
            </div>
        </div>

        <div class="form-group-custom">
            <label for="confirm_password" class="form-label-custom">Konfirmasi Password</label>
            <div class="input-wrapper">
                <i class="fas fa-lock input-icon"></i>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-input-custom"
                    placeholder="Ulangi password Anda"
                    required>
                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <small id="matchText" class="match-text d-none"></small>
        </div>

        <div class="form-check-custom mb-3">
            <input type="checkbox" id="terms" name="terms" class="form-check-input-custom" required>
            <label for="terms" class="form-check-label-custom">
                Saya setuju dengan <a href="#" class="text-primary">Syarat & Ketentuan</a> dan <a href="#" class="text-primary">Kebijakan Privasi</a>
            </label>
        </div>

        <button type="submit" class="btn-submit" id="btnRegister">
            <span class="btn-text">Daftar Sekarang — Gratis!</span>
            <i class="fas fa-arrow-right ms-2"></i>
        </button>
    </form>

    <p class="auth-switch">
        Sudah punya akun?
        <a href="<?= base_url('/masuk') ?>">Masuk di sini</a>
    </p>
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

// Password strength checker
document.getElementById('password').addEventListener('input', function() {
    const pwd = this.value;
    const indicator = document.getElementById('pwdStrength');
    const fill = document.getElementById('strengthFill');
    const text = document.getElementById('strengthText');

    if (pwd.length === 0) { indicator.style.display = 'none'; return; }
    indicator.style.display = 'block';

    let score = 0;
    if (pwd.length >= 8) score++;
    if (/[A-Z]/.test(pwd)) score++;
    if (/[0-9]/.test(pwd)) score++;
    if (/[^A-Za-z0-9]/.test(pwd)) score++;

    const levels = [
        { pct: '25%', color: '#ef4444', label: 'Sangat Lemah' },
        { pct: '50%', color: '#f97316', label: 'Lemah' },
        { pct: '75%', color: '#eab308', label: 'Cukup Kuat' },
        { pct: '100%', color: '#22c55e', label: 'Kuat' },
    ];
    const lvl = levels[score - 1] || levels[0];
    fill.style.width = lvl.pct;
    fill.style.background = lvl.color;
    text.textContent = lvl.label;
    text.style.color = lvl.color;
});

// Confirm password match
document.getElementById('confirm_password').addEventListener('input', function() {
    const pwd = document.getElementById('password').value;
    const matchText = document.getElementById('matchText');
    matchText.classList.remove('d-none');
    if (this.value === pwd) {
        matchText.textContent = '✓ Password cocok';
        matchText.className = 'match-text text-success';
    } else {
        matchText.textContent = '✗ Password tidak cocok';
        matchText.className = 'match-text text-danger';
    }
});

document.getElementById('formRegister').addEventListener('submit', function(e) {
    const btn = document.getElementById('btnRegister');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mendaftarkan...';
    btn.disabled = true;
});
</script>
<?= $this->endSection() ?>
