<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<?php $role = session()->get('user_role') ?? 'superadmin'; ?>
<style>
    /* ULTRA-MINIMALIST B2B STYLES */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #000; font-weight: 800; letter-spacing: -0.03em; }
    .text-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: #6b7280; font-weight: 600; }
    
    .flat-tabs { display: flex; gap: 32px; border-bottom: 1px solid #e5e7eb; margin-bottom: 32px; }
    .flat-tab { padding: 12px 0; color: #6b7280; font-weight: 600; cursor: pointer; position: relative; }
    .flat-tab.active { color: #000; }
    .flat-tab.active::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 2px; background: #000; }
    
    .pill-btn { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 0.85rem; color: #374151; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .pill-btn:hover { background: #f9fafb; border-color: #9ca3af; }
    
    .timeline-dot { width: 12px; height: 12px; border-radius: 50%; background: #d1d5db; position: relative; z-index: 2; }
    .timeline-dot.active { background: #f97316; }
    
    .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
    
    .header-minimal { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .user-profile-large { display: flex; align-items: center; gap: 24px; }
    .avatar-large { width: 100px; height: 100px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #9ca3af; overflow: hidden; }

    /* OVERRIDE SWEETALERT TO MATCH ENTERPRISE MINIMALIST THEME */
    div:where(.swal2-container) div:where(.swal2-popup) {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        border-radius: 12px !important;
        border: 1px solid #e5e7eb !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
        padding: 24px !important;
    }
    div:where(.swal2-container) h2:where(.swal2-title) {
        font-weight: 800 !important;
        color: #000 !important;
        letter-spacing: -0.03em !important;
    }
    div:where(.swal2-container) .swal2-html-container {
        color: #6b7280 !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
    }
    div:where(.swal2-container) button:where(.swal2-styled).swal2-confirm {
        background-color: #000 !important;
        color: #fff !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
        padding: 10px 24px !important;
        box-shadow: none !important;
    }
    div:where(.swal2-container) button:where(.swal2-styled).swal2-cancel {
        background-color: #fff !important;
        color: #374151 !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        font-weight: 700 !important;
        padding: 10px 24px !important;
    }
    div:where(.swal2-container) input:where(.swal2-input) {
        border-radius: 8px !important;
        border: 1px solid #d1d5db !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
        font-weight: 500 !important;
        box-shadow: none !important;
    }
    div:where(.swal2-container) input:where(.swal2-input):focus {
        border: 2px solid #000 !important;
        outline: none !important;
        box-shadow: none !important;
    }
</style>

<div class="header-minimal">
    <div class="user-profile-large">
        <div class="avatar-large">
            <img src="https://ui-avatars.com/api/?name=Edu+Nusa&background=f3f4f6&color=000&size=100&bold=true" alt="Platform">
        </div>
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fas fa-chart-line text-muted"></i>
                <span class="text-label">Live Overview - <?= date('h:i A') ?></span>
                <span class="badge bg-danger rounded-pill px-2" style="font-size: 0.6rem;">REC</span>
            </div>
            <h1 class="mb-1" style="font-size: 2.5rem;"><?= esc($data["platform"]["name"]) ?></h1>
            <p class="text-muted fw-medium"><?= esc($data["platform"]["phone"]) ?> &bull; <a href="#" class="text-dark"><?= esc($data["platform"]["website"]) ?></a></p>
        </div>
    </div>
    <div>
        <button id="btn-settings" class="btn btn-light rounded-circle border p-2 text-muted shadow-sm" style="width: 40px; height: 40px;"><i class="fas fa-cog"></i></button>
    </div>
</div>

<!-- Action Bar -->
<div class="d-flex gap-3 mb-4 p-2 bg-light rounded-3" style="max-width: fit-content; border: 1px solid #f3f4f6;">
    <?php if($data["action_buttons"]["suspend"]): ?>
    <?php if(isset($data["platform"]["is_suspended"]) && $data["platform"]["is_suspended"]): ?>
        <button id="btn-suspend" onclick="suspendAction()" class="btn btn-sm btn-success fw-bold px-3"><i class="fas fa-play me-2"></i> Resume</button>
    <?php else: ?>
        <button id="btn-suspend" onclick="suspendAction()" class="btn btn-sm text-dark fw-bold px-3"><i class="fas fa-pause me-2"></i> Suspend</button>
    <?php endif; ?>
<?php endif; ?>
    <?php if($data["action_buttons"]["mute_alert"]): ?>
    <?php if(isset($data["platform"]["is_muted"]) && $data["platform"]["is_muted"]): ?>
        <button id="btn-mute" onclick="muteAction()" class="btn btn-sm btn-danger fw-bold px-3"><i class="fas fa-bell me-2"></i> Unmute alert</button>
    <?php else: ?>
        <button id="btn-mute" onclick="muteAction()" class="btn btn-sm text-dark fw-bold px-3"><i class="fas fa-microphone-slash me-2"></i> Mute alert</button>
    <?php endif; ?>
<?php endif; ?>
    
    <?php if($data["action_buttons"]["modules"]): ?><button id="btn-modules" onclick="modulesAction()" class="btn btn-sm text-dark fw-bold px-3"><i class="fas fa-th me-2"></i> Modules</button><?php endif; ?>
    <?php if($data["action_buttons"]["add_admin"]): ?><button id="btn-add-admin" onclick="addAdminAction()" class="btn btn-sm text-dark fw-bold px-3"><i class="fas fa-user-plus me-2"></i> Add admin</button><?php endif; ?>
    <?php if($data["action_buttons"]["shut_down"]): ?><button id="btn-shutdown" onclick="shutdownAction()" class="btn btn-sm btn-danger fw-bold px-4 ms-2 rounded-3"><i class="fas fa-power-off me-2"></i> Shut down</button><?php endif; ?>
</div>

<!-- Tabs -->
<div class="flat-tabs">
    <div class="flat-tab active" data-tab="dashboard">Dashboard</div>
    <?php if(in_array($role, ['superadmin', 'finance'])): ?>
    <div class="flat-tab" data-tab="transactions">Transactions</div>
    <?php endif; ?>
    <div class="flat-tab" data-tab="analytics">Analytics</div>
    <?php if(in_array($role, ['superadmin', 'finance'])): ?>
    <div class="flat-tab" data-tab="reports">Reports</div>
    <?php endif; ?>
</div>

<!-- Action right -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex gap-4">
        <span class="text-dark fw-bold" style="font-size: 0.9rem;"><i class="fas fa-desktop me-2 text-muted"></i> System health</span>
        <span class="text-dark fw-bold" style="font-size: 0.9rem;"><i class="fas fa-video me-2 text-muted"></i> Live classes</span>
    </div>
    <div class="d-flex gap-3">
        <button id="btn-export" class="btn btn-sm btn-link text-dark fw-bold text-decoration-none">Export data <i class="fas fa-chevron-down ms-1"></i></button>
        <button id="btn-saved" class="pill-btn"><i class="far fa-heart"></i> Saved metrics</button>
    </div>
</div>

<div class="grid-container" id="tab-content-dashboard">
    <!-- Left Column: Platform Profile -->
    <div>
        <h3 class="mb-4">Platform profile</h3>
        <p class="text-muted fw-bold d-flex align-items-center gap-2 mb-4"><i class="fas fa-trophy"></i> Top E-Learning 2026</p>
        
        <div class="row mb-5">
            <div class="col-6">
                <div class="text-label mb-1">Established</div>
                <div class="fw-bold"><?= esc($data['platform']['established'] ?? 'May 13, 2022') ?></div>
            </div>
            <div class="col-6">
                <div class="text-label mb-1">Last Update</div>
                <div class="fw-bold"><?= esc($data['platform']['last_update'] ?? 'Today') ?></div>
            </div>
        </div>
        
        <div class="mb-5">
            <div class="text-label mb-1">Data Center</div>
            <div class="fw-bold"><?= esc($data['platform']['data_center'] ?? 'GCP Asia-Southeast2, Jakarta, Indonesia') ?></div>
        </div>
        
        <div class="d-flex align-items-center gap-2 mb-5">
            <div class="text-label">SLA Contract</div>
            <i class="fas fa-file-contract fs-4"></i>
        </div>
        
        <h4 class="mb-4 d-flex justify-content-between align-items-center">
            Recent activity <i class="fas fa-arrow-right text-muted" style="font-size: 1rem; cursor:pointer;"></i>
        </h4>
        
        <?php if(!empty($data['recent_activities'])): ?>
            <?php foreach($data['recent_activities'] as $act): ?>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background: <?= esc($act['icon_color']) ?>;">
                    <i class="<?= esc($act['icon']) ?> fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold"><?= esc($act['title']) ?></div>
                    <div class="text-muted small"><?= esc($act['description']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-3 bg-light d-flex align-items-center justify-content-center text-dark" style="width: 48px; height: 48px;">
                    <i class="fab fa-discord fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold">Community forum</div>
                    <div class="text-muted small">Today, 11:00 AM</div>
                </div>
            </div>
            
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 text-white d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #f97316;">
                    <i class="fab fa-instagram fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold">Instagram comment</div>
                    <div class="text-muted small">Today, 10:40 AM</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if(in_array($role, ['superadmin', 'finance'])): ?>
<!-- Right Column: Latest Metrics -->
    <div>
        <h3 class="mb-2">Latest revenue</h3>
        <p class="text-muted mb-4">Most recent transaction</p>
        
        <div class="d-flex gap-4 mb-4 pb-4 border-bottom">
            <a href="javascript:void(0)" id="btn-refund" class="text-dark fw-bold text-decoration-none d-flex align-items-center gap-2"><i class="far fa-times-circle"></i> Refund order</a>
            <a href="javascript:void(0)" id="btn-trace" class="text-dark fw-bold text-decoration-none d-flex align-items-center gap-2"><i class="fas fa-map-marker-alt"></i> Trace user</a>
            <a href="javascript:void(0)" id="btn-view-course" class="text-dark fw-bold text-decoration-none d-flex align-items-center gap-2"><i class="fas fa-box"></i> View course</a>
        </div>
        
        <div class="d-flex gap-4 mb-4">
            <div class="rounded-3 bg-light" style="width: 160px; height: 160px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 1px solid #e5e7eb;">
                <img src="<?= esc($data['latest_revenue']['course_thumbnail']) ?>" onerror="this.src='https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=200&auto=format&fit=crop'" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            
            <div class="pt-2">
                <div class="text-muted small mb-1">Last update: <?= esc($data['latest_revenue']['last_update'] ?? 'today, 01:45 PM') ?></div>
                <h4 class="mb-4"><?= esc($data['latest_revenue']['course_name'] ?? 'Full-Stack Laravel') ?></h4>
                <div class="text-label mb-1">Amount Paid</div>
                <div class="fw-bold fs-5"><?= esc($data['latest_revenue']['amount'] ?? 'Rp 550,000') ?></div>
            </div>
        </div>
        
        <div class="row mb-5">
            <div class="col-6">
                <div class="text-label mb-1">Transaction # ID</div>
                <div class="fw-bold d-flex align-items-center gap-2"><?= esc($data['latest_revenue']['trx_id'] ?? 'TRX-458905840958490') ?> <i class="far fa-copy text-muted" id="btn-copy-trx" style="cursor:pointer;" title="Copy to clipboard"></i></div>
            </div>
            <div class="col-6">
                <div class="text-label mb-1">Payment method</div>
                <div class="fw-bold"><?= esc($data['latest_revenue']['payment_method'] ?? 'Bank Transfer (BCA)') ?></div>
            </div>
        </div>
        
        <!-- Status Timeline -->
        <div class="position-relative d-flex justify-content-between mb-2">
            <div style="position: absolute; top: 5px; left: 10px; right: 10%; height: 2px; background: #e5e7eb; z-index: 1;"></div>
            <div style="position: absolute; top: 5px; left: 10px; width: 60%; height: 2px; background: #f97316; z-index: 1;"></div>
            
            <div class="text-center" style="width: 80px; position: relative; z-index: 2;">
                <div class="timeline-dot mb-2 mx-auto" style="background: #f97316;"></div>
                <div class="text-label" style="font-size: 0.65rem;">Created</div>
                <div class="fw-bold small"><?= esc($data['latest_revenue']['created_at']) ?></div>
            </div>
            <div class="text-center" style="width: 80px; position: relative; z-index: 2;">
                <div class="timeline-dot mb-2 mx-auto" <?= ($data['latest_revenue']['payment_date'] !== '-' || $data['latest_revenue']['status'] === 'paid') ? 'style="background: #f97316;"' : '' ?>></div>
                <div class="text-label" style="font-size: 0.65rem;">Paid</div>
                <div class="fw-bold small"><?= esc($data['latest_revenue']['payment_date']) ?></div>
            </div>
            <div class="text-center" style="width: 80px; position: relative; z-index: 2;">
                <div class="timeline-dot mb-2 mx-auto" <?= ($data['latest_revenue']['status'] === 'paid') ? 'style="background: #f97316;"' : 'class="timeline-dot active mb-2 mx-auto"' ?>></div>
                <div class="text-label" style="font-size: 0.65rem;">Enrolled</div>
                <div class="fw-bold small"><?= ($data['latest_revenue']['status'] === 'paid' && $data['latest_revenue']['payment_date'] !== '-') ? esc($data['latest_revenue']['payment_date']) : '-' ?></div>
            </div>
            <div class="text-center" style="width: 80px; position: relative; z-index: 2;">
                <div class="timeline-dot mb-2 mx-auto"></div>
                <div class="text-label" style="font-size: 0.65rem;">Completed</div>
                <div class="fw-bold small text-muted">-</div>
            </div>
        </div>
        
    </div>
    <?php else: ?>
    <div>
        <h3 class="mb-2">Analytics</h3>
        <p class="text-muted">Your role does not have access to financial metrics.</p>
    </div>
    <?php endif; ?>
</div>


<!-- Placeholder for other tabs -->
<div class="grid-container" id="tab-content-transactions" style="display: none;">
    <div style="grid-column: 1 / -1;" class="text-center py-5">
        <i class="fas fa-receipt fs-1 text-muted mb-3"></i>
        <h3 class="fw-bold">Recent Transactions</h3>
        <p class="text-muted">Loading transaction data from secure server...</p>
    </div>
</div>

<div class="grid-container" id="tab-content-analytics" style="display: none;">
    <div style="grid-column: 1 / -1;" class="text-center py-5">
        <i class="fas fa-chart-bar fs-1 text-muted mb-3"></i>
        <h3 class="fw-bold">Analytics Engine</h3>
        <p class="text-muted">Generating multi-dimensional data charts...</p>
    </div>
</div>

<div class="grid-container" id="tab-content-reports" style="display: none;">
    <div style="grid-column: 1 / -1;" class="text-center py-5">
        <i class="fas fa-file-pdf fs-1 text-muted mb-3"></i>
        <h3 class="fw-bold">Automated Reports</h3>
        <p class="text-muted">Processing periodic summaries for Q3 2026...</p>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('js/sweetalert2.all.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Toast Configuration
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
    });

    // 1. TABS LOGIC
    const tabs = document.querySelectorAll('.flat-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active from all tabs
            tabs.forEach(t => t.classList.remove('active'));
            // Add active to clicked
            this.classList.add('active');
            
            // Hide all tab contents
            document.querySelectorAll('.grid-container').forEach(content => {
                content.style.display = 'none';
            });
            
            // Show target content
            const targetId = 'tab-content-' + this.getAttribute('data-tab');
            const target = document.getElementById(targetId);
            if(target) {
                target.style.display = 'grid'; // Grid container needs grid display
            }
        });
    });

    // 2. BUTTONS LOGIC
    document.getElementById('btn-settings')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Platform Settings',
            text: 'Opening configuration panel...',
            icon: 'info',
            confirmButtonColor: '#000'
        });
    });

    
    
    
    window.suspendAction = function() {
        const isSuspended = document.getElementById('btn-suspend').innerText.includes('Resume');
        Swal.fire({
            title: isSuspended ? 'Resume Server?' : 'Suspend Server?',
            text: isSuspended ? 'This will reactivate the platform for all users.' : 'This will temporarily halt all active sessions.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: isSuspended ? '#10b981' : '#000',
            cancelButtonColor: '#d33',
            confirmButtonText: isSuspended ? 'Yes, resume it!' : 'Yes, suspend it!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('<?= base_url("admin/api/toggle-suspend") ?>', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'}
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire(data.is_suspended ? 'Suspended!' : 'Resumed!', data.is_suspended ? 'Server is now offline.' : 'Server is back online.', 'success')
                        .then(() => location.reload());
                    }
                });
            }
        });
    };

    
    window.muteAction = function() {
        fetch('<?= base_url("admin/api/toggle-mute") ?>', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    icon: data.is_muted ? 'success' : 'info',
                    title: data.is_muted ? 'System alerts muted' : 'System alerts restored'
                }).then(() => location.reload());
            }
        });
    };

    // TRANSACTION NOTIFICATION & MUTE LOGIC
    let isMuted = <?= isset($data["platform"]["is_muted"]) && $data["platform"]["is_muted"] ? "true" : "false" ?>;
    let lastOrderId = 0;
    let isFirstPoll = true;

    // Proper Synthesized "Ting" (Bell/Chime sound)
    let audioCtx;
    
    // Unlock AudioContext on first user interaction
    document.body.addEventListener("click", function() {
        if (!audioCtx) {
            audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        } else if (audioCtx.state === "suspended") {
            audioCtx.resume();
        }
    });

    function playTingSound() {
        if (isMuted) return;
        if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        if (audioCtx.state === "suspended") audioCtx.resume();
        
        try {
            const time = audioCtx.currentTime;
            
            // Soft Marimba / Woodblock synthesizer
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = "sine";
            
            // Woodblock frequency (mid-low, warm tone)
            osc.frequency.setValueAtTime(450, time);
            // Slight pitch drop for organic "thump" feel
            osc.frequency.exponentialRampToValueAtTime(400, time + 0.1);
            
            // Very fast attack, quick decay (staccato)
            gain.gain.setValueAtTime(0, time);
            gain.gain.linearRampToValueAtTime(0.6, time + 0.005);
            gain.gain.exponentialRampToValueAtTime(0.001, time + 0.3);
            
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            
            osc.start(time);
            osc.stop(time + 0.35);
        } catch(e) {
            console.warn("Audio error:", e);
        }
    }

    // Polling function
    function checkNewTransactions() {
        fetch("<?= base_url('admin/api/check-new-orders') ?>?last_id=" + lastOrderId)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    isMuted = (data.is_muted === 1);
                    
                    if (data.new_orders && data.new_orders.length > 0) {
                        if (!isFirstPoll) {
                            // Play sound if not muted
                            if (!isMuted) playTingSound();
                            
                            // Show toast for each new order
                            data.new_orders.forEach(order => {
                                Toast.fire({
                                    icon: "success",
                                    title: "New Transaction: Rp " + new Intl.NumberFormat("id-ID").format(order.amount),
                                    timer: 5000
                                });
                            });
                        }
                        lastOrderId = data.current_max_id;
                    }
                    isFirstPoll = false;
                }
            })
            .catch(err => console.error("Polling error:", err));
    }

    let monitoringInterval = setInterval(checkNewTransactions, 5000);
    
    // Initial call
    checkNewTransactions();

    // Attach to the Mute alert toggle to update local state immediately
    const muteBtn = document.getElementById("btn-mute");
    if (muteBtn) {
        muteBtn.addEventListener("click", function() {
            // Local state updates optimistically; the polling will sync it too
            isMuted = !isMuted;
        });
    }

    
        
    window.modulesAction = function() {
        Swal.fire({
            title: 'Modules Management',
            html: 'Active Modules: <b>14</b><br>Inactive Modules: <b>2</b>',
            icon: 'info',
            confirmButtonColor: '#000'
        });
    };
    
    window.addAdminAction = function() {
        Swal.fire({
            title: 'Invite Admin',
            input: 'email',
            inputLabel: 'Admin Email Address',
            inputPlaceholder: 'Enter email address',
            showCancelButton: true,
            confirmButtonColor: '#000'
        }).then((result) => {
            if (result.value) {
                Swal.fire('Invited!', 'Invitation sent to ' + result.value, 'success');
            }
        });
    };
    
    window.shutdownAction = function() {
        Swal.fire({
            title: 'EMERGENCY SHUTDOWN',
            text: 'Are you absolutely sure? This will disconnect all users!',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Execute Shutdown'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire('Offline', 'System is now completely offline.', 'success');
            }
        });
    };
document.getElementById('btn-export')?.addEventListener('click', () => {
        Toast.fire({ icon: 'success', title: 'Data exported as CSV' });
    });

    document.getElementById('btn-saved')?.addEventListener('click', () => {
        Toast.fire({ icon: 'info', title: 'Opening saved metrics dashboard' });
    });

    document.getElementById('btn-refund')?.addEventListener('click', () => {
        Swal.fire({
            title: 'Process Refund?',
            text: "Refund Rp 550,000 for TRX-458905840958490?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#000',
            confirmButtonText: 'Process'
        }).then((result) => {
            if (result.isConfirmed) {
                Toast.fire({ icon: 'success', title: 'Refund processed' });
            }
        });
    });

    document.getElementById('btn-trace')?.addEventListener('click', () => {
        Swal.fire({
            title: 'User Tracing',
            html: 'User IP: <b>103.24.45.12</b><br>Device: <b>MacBook Pro (Safari)</b>',
            icon: 'info',
            confirmButtonColor: '#000'
        });
    });

    document.getElementById('btn-view-course')?.addEventListener('click', () => {
        Toast.fire({ icon: 'info', title: 'Redirecting to Full-Stack Laravel course...' });
    });

    document.getElementById('btn-copy-trx')?.addEventListener('click', () => {
        navigator.clipboard.writeText('TRX-458905840958490');
        Toast.fire({ icon: 'success', title: 'Transaction ID copied!' });
    });
});
</script>
<?= $this->endSection() ?>
