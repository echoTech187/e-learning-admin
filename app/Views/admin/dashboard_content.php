<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<?php $role = session()->get('user_role') ?? 'superadmin'; ?>
<style>
    /* ULTRA-MINIMALIST B2B STYLES */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #000; font-weight: 800; letter-spacing: -0.03em; }
    .text-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: #6b7280; font-weight: 600; }
    
    .pill-btn { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 0.85rem; color: #374151; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .pill-btn:hover { background: #f9fafb; border-color: #9ca3af; }
    
    .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
    
    .header-minimal { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .user-profile-large { display: flex; align-items: center; gap: 24px; }
    .avatar-large { width: 100px; height: 100px; background: #f3f4f6; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #9ca3af; overflow: hidden; }
</style>

<div class="header-minimal">
    <div class="user-profile-large">
        <div class="avatar-large">
            <i class="fas fa-book-open text-dark"></i>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fas fa-layer-group text-muted"></i>
                <span class="text-label">Content Pipeline - <?= date('h:i A') ?></span>
            </div>
            <h1 class="mb-1" style="font-size: 2.5rem;">Content Manager</h1>
            <p class="text-muted fw-medium">Manage courses, modules, and learning materials</p>
        </div>
    </div>
    <div>
        <button class="btn btn-dark rounded-3 px-4 fw-bold py-2"><i class="fas fa-plus me-2"></i> New Course</button>
    </div>
</div>

<hr class="my-5 text-muted">

<div class="grid-container">
    <!-- Left Column: Metrics -->
    <div>
        <h3 class="mb-4">Content metrics</h3>
        
        <div class="row mb-5">
            <div class="col-6">
                <div class="text-label mb-1">Published Courses</div>
                <div class="fw-bold fs-1"><?= esc($data['courses_published']) ?></div>
            </div>
            <div class="col-6">
                <div class="text-label mb-1">Drafts / In Review</div>
                <div class="fw-bold fs-1 text-warning"><?= esc($data['courses_draft']) ?></div>
            </div>
        </div>
        
        <div class="mb-5">
            <div class="text-label mb-1">Total Mentors</div>
            <div class="fw-bold d-flex align-items-center gap-2">24 <span class="badge bg-success rounded-pill" style="font-size: 0.6rem;">Active</span></div>
        </div>
    </div>
    
    <!-- Right Column: Recent Drafts -->
    <div>
        <h3 class="mb-4 d-flex justify-content-between align-items-center">
            Recent activity <i class="fas fa-arrow-right text-muted" style="font-size: 1rem; cursor:pointer;"></i>
        </h3>
        
        <?php if(!empty($data['recent_courses'])): ?>
            <?php foreach($data['recent_courses'] as $course): ?>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="rounded-3 d-flex align-items-center justify-content-center text-white" style="width: 48px; height: 48px; background: <?= $course['status'] == 'published' ? '#10b981' : '#f59e0b' ?>;">
                    <i class="<?= $course['status'] == 'published' ? 'fas fa-check' : 'fas fa-pen' ?> fs-4"></i>
                </div>
                <div>
                    <div class="fw-bold"><?= esc($course['title']) ?></div>
                    <div class="text-muted small">Status: <?= ucfirst($course['status']) ?> &bull; <?= date('M d, Y', strtotime($course['created_at'])) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-muted">No recent content activity found.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
