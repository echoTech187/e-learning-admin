/**
 * EduNusa E-Learning — Main JavaScript
 */

'use strict';

// ============================================================
// GLOBAL UTILITIES
// ============================================================

/**
 * Format currency to IDR
 */
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(amount);
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const colors = {
        success: '#22C55E',
        error: '#EF4444',
        warning: '#F59E0B',
        info: '#6C47FF',
    };
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ',
    };

    const toast = document.createElement('div');
    toast.className = 'edu-toast';
    toast.innerHTML = `
        <span class="toast-icon" style="background:${colors[type]}">${icons[type]}</span>
        <span class="toast-message">${message}</span>
    `;
    toast.style.cssText = `
        position: fixed; bottom: 24px; right: 24px; z-index: 9999;
        background: #fff; border-radius: 12px; padding: 14px 20px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15); display: flex;
        align-items: center; gap: 12px; font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9rem; font-weight: 500; max-width: 360px;
        border-left: 4px solid ${colors[type]};
        animation: slideInToast 0.3s ease;
    `;

    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInToast {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .toast-icon {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 0.8rem; font-weight: 700;
            flex-shrink: 0;
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideInToast 0.3s ease reverse';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

// ============================================================
// PAGE LOADER
// ============================================================
document.addEventListener('DOMContentLoaded', function () {
    // Remove loader
    const loader = document.getElementById('pageLoader');
    if (loader) {
        loader.style.opacity = '0';
        setTimeout(() => loader.remove(), 300);
    }

    // Initialize tooltips (Bootstrap)
    const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(el => new bootstrap.Tooltip(el));

    // Auto-close alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-auto-close');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'all 0.3s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Animate elements on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
});

// ============================================================
// NOTIFICATION COUNTER (AJAX)
// ============================================================
function updateNotificationCount() {
    fetch('/api/notifikasi/jumlah', {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(res => res.json())
    .then(data => {
        const badge = document.getElementById('notifCount');
        if (badge) {
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'flex' : 'none';
        }
    })
    .catch(() => {});
}

// ============================================================
// FORM HELPERS
// ============================================================

/**
 * Confirm delete dialog
 */
function confirmDelete(formId, message = 'Yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.') {
    if (confirm(message)) {
        document.getElementById(formId).submit();
    }
}

/**
 * Preview uploaded image
 */
function previewImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = e => {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });
}

// ============================================================
// CSRF TOKEN for AJAX
// ============================================================
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : '';
}

// Global AJAX setup
if (typeof window !== 'undefined') {
    window.eduAPI = {
        post: function(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(data)
            }).then(r => r.json());
        },
        get: function(url) {
            return fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(r => r.json());
        }
    };
}
