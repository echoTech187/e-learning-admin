<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>

<style>
    /* B2B Premium CRM Style - Details View */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; font-weight: 800; letter-spacing: -0.02em; }
    
    .agy-label { font-size: 0.8rem; color: #94a3b8; font-weight: 600; margin-bottom: 4px; display: block; }
    .agy-value { font-size: 1rem; color: #0f172a; font-weight: 600; }
    
    .divider-right { border-right: 1px solid #e2e8f0; }
    
    .section-title { font-size: 1.25rem; margin-bottom: 32px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; display: flex; justify-content: space-between; align-items: center; }
    
    /* Buttons */
    .agy-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 24px; border-radius: 99px; font-weight: 700; font-size: 0.95rem;
        font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: all 0.2s ease;
        border: none; outline: none; text-decoration: none; gap: 8px;
    }
    .agy-btn-primary { background: #0f172a; color: white; }
    .agy-btn-primary:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); color: white; }
    .agy-btn-secondary { background: #f8fafc; color: #0f172a; border: 1px solid #e2e8f0; }
    .agy-btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }
    .agy-btn-back { color: #64748b; font-weight: 600; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s; }
    .agy-btn-back:hover { color: #0f172a; }
    
    /* Thumbnail */
    .course-thumb { width: 100%; border-radius: 16px; object-fit: cover; aspect-ratio: 16/9; background: #f8fafc; border: 1px solid #e2e8f0; }
</style>

<style>
    /* Curriculum Builder Styles */
    .curriculum-section {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        margin-bottom: 24px;
        background: #fff;
        overflow: hidden;
    }
    .curriculum-section-header {
        background: #f8fafc;
        padding: 16px 20px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .curriculum-section-title {
        font-weight: 700;
        font-size: 1.05rem;
        color: #0f172a;
        margin: 0;
    }
    .curriculum-section-body {
        padding: 0;
    }
    .lesson-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.2s;
    }
    .lesson-item:hover { background: #f8fafc; }
    .lesson-item:last-child { border-bottom: none; }
    .lesson-icon {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        background: #f1f5f9; color: #64748b; margin-right: 16px;
    }
    
    /* B2B Custom Modal */
    .agy-modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
        z-index: 1050; display: none; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.3s;
    }
    .agy-modal {
        background: #fff; width: 100%; max-width: 500px;
        border-radius: 20px; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        overflow: hidden;
    }
    .agy-modal-backdrop.show { opacity: 1; display: flex; }
    .agy-modal-backdrop.show .agy-modal { transform: translateY(0); }
    .agy-modal-header { padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .agy-modal-title { font-weight: 800; font-size: 1.25rem; margin: 0; color: #0f172a; }
    .agy-modal-close { background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer; transition: color 0.2s; }
    .agy-modal-close:hover { color: #0f172a; }
    .agy-modal-body { padding: 24px; }
    .agy-modal-footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }
    
    /* Custom Inputs */
    .agy-input {
        width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; color: #0f172a;
        background: #f8fafc; transition: all 0.2s; outline: none;
    }
    .agy-input:focus { background: #fff; border-color: #94a3b8; box-shadow: 0 0 0 4px #f1f5f9; }
</style>


<div class="container-fluid p-0">
    
    <!-- Top Header Navigation -->
    <div class="d-flex align-items-center justify-content-between mb-5">
        <a href="/admin/program" class="agy-btn-back">
            <i class="fas fa-arrow-left"></i> Back to catalog
        </a>
        <div class="d-flex gap-3">
            <a href="/admin/program/course/builder/<?= $course['id'] ?>" class="agy-btn agy-btn-secondary">
                <i class="fas fa-cog"></i> Edit Settings
            </a>
            <button class="agy-btn agy-btn-primary" onclick="AgyToast.info('Preview will be available in Phase 4.')">
                <i class="fas fa-external-link-alt"></i> Preview
            </button>
        </div>
    </div>

    <!-- Main Title -->
    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="badge bg-<?= $course['status'] == 'published' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $course['status'] == 'published' ? 'success' : 'secondary' ?> rounded-pill px-3 py-2 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                <?= esc($course['status']) ?>
            </span>
            <span class="text-muted fw-bold" style="font-size: 0.85rem;"><i class="fas fa-folder-open text-primary me-2"></i> <?= esc($course['category_name']) ?></span>
        </div>
        <h1 class="display-5 fw-bold text-dark mb-0"><?= esc($course['title']) ?></h1>
    </div>

    <div class="row g-0">
        <!-- Left Column: Curriculum -->
        <div class="col-lg-8 divider-right pe-lg-5 pb-5">
            
            <div class="section-title">
                Curriculum Structure
                <button class="agy-btn agy-btn-secondary" style="padding: 8px 16px; font-size: 0.85rem;" onclick="openSectionModal()">
                    <i class="fas fa-plus"></i> Add Section
                </button>
            </div>
            
            <div id="curriculum-container">
            <?php if(empty($sections)): ?>
            <div class="text-center py-4" id="empty-curriculum-state">
                <div class="mb-4 text-muted" style="font-size: 3rem;"><i class="fas fa-clipboard-list"></i></div>
                <h5 class="fw-bold">No curriculum yet</h5>
                <p class="text-muted mb-4">Start building your course structure by adding sections and lessons.</p>
                <button class="agy-btn agy-btn-primary" onclick="openSectionModal()">
                    Create First Section
                </button>
            </div>
            <?php else: ?>
                <?php foreach($sections as $sec): ?>
                <div class="curriculum-section">
                    <div class="curriculum-section-header">
                        <h4 class="curriculum-section-title"><?= esc($sec['title']) ?></h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light border" onclick="openEditSectionModal('<?= $sec['id'] ?>', '<?= esc($sec['title']) ?>')"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-light border text-danger" onclick="deleteSection('<?= $sec['id'] ?>', this)"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="curriculum-section-body">
                        <?php if(empty($sec['lessons'])): ?>
                            <div class="p-4 text-center text-muted" style="font-size: 0.9rem;">
                                No lessons in this section yet.
                            </div>
                        <?php else: ?>
                            <?php foreach($sec['lessons'] as $les): ?>
                                <div class="lesson-item">
                                    <div class="d-flex align-items-center">
                                        <div class="lesson-icon">
                                            <?php 
                                            $icon = 'fa-play';
                                            if($les['type'] == 'document') $icon = 'fa-file-pdf';
                                            if($les['type'] == 'text') $icon = 'fa-file-alt';
                                            if($les['type'] == 'quiz') $icon = 'fa-tasks';
                                            ?>
                                            <i class="fas <?= $icon ?>"></i>
                                        </div>
                                        <div class="fw-bold text-dark"><?= esc($les['title']) ?></div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-light text-muted border" onclick="window.location.href='/admin/program/lesson/editor/<?= $les['id'] ?>'"><i class="fas fa-cog"></i></button>
                                        <button class="btn btn-sm btn-light text-danger border" onclick="deleteLesson('<?= $les['id'] ?>', this)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <div class="p-3 bg-light border-top">
                            <button class="btn btn-sm btn-outline-secondary w-100" style="border-style: dashed;" onclick="openLessonModal('<?= $sec['id'] ?>')">
                                <i class="fas fa-plus"></i> Add Lesson
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            </div>

            
            <div class="section-title mt-5">
                About this course
            </div>
            <p class="text-secondary" style="line-height: 1.8;">
                <?= nl2br(esc($course['description'] ?: 'No description provided.')) ?>
            </p>
            
        </div>
        
        <!-- Right Column: Meta Info -->
        <div class="col-lg-4 ps-lg-5 pt-5 pt-lg-0">
            <div class="position-sticky" style="top: 24px;">
                
                <?php if(!empty($course['thumbnail'])): ?>
                    <img src="/<?= esc($course['thumbnail']) ?>" class="course-thumb mb-4" alt="Course Thumbnail">
                <?php else: ?>
                    <div class="course-thumb mb-4 d-flex align-items-center justify-content-center text-muted">
                        <i class="fas fa-image fa-3x opacity-25"></i>
                    </div>
                <?php endif; ?>
                
                <h4 class="section-title">At a glance</h4>
                
                <div class="d-flex flex-column gap-4">
                    <div>
                        <span class="agy-label">Instructor</span>
                        <div class="agy-value d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                <i class="fas fa-user text-muted"></i>
                            </div>
                            <?= esc($course['instructor_name']) ?>
                        </div>
                    </div>
                    
                    <div>
                        <span class="agy-label">Price</span>
                        <div class="agy-value fs-4 text-success">
                            <?= $course['price'] > 0 ? 'Rp ' . number_format($course['price'], 0, ',', '.') : 'Free' ?>
                        </div>
                    </div>
                    
                    <div>
                        <span class="agy-label">Difficulty Level</span>
                        <div class="agy-value text-capitalize">
                            <?= esc($course['level']) ?>
                        </div>
                    </div>
                    
                    <div>
                        <span class="agy-label">Last Updated</span>
                        <div class="agy-value">
                            <?= date('M d, Y', strtotime($course['updated_at'])) ?>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>

<!-- Lesson Modal -->
<div class="agy-modal-backdrop" id="lessonModal">
    <div class="agy-modal">
        <div class="agy-modal-header">
            <h3 class="agy-modal-title">Add New Lesson</h3>
            <button class="agy-modal-close" onclick="closeLessonModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="agy-modal-body">
            <input type="hidden" id="editSectionId">
            <input type="hidden" id="activeSectionId">
            
            <div class="mb-4">
                <label class="agy-label text-dark fw-bold mb-2">Lesson Title</label>
                <input type="text" id="lessonTitle" class="agy-input" placeholder="e.g. Introduction to Framework, etc.">
            </div>
            
            <div>
                <label class="agy-label text-dark fw-bold mb-2">Lesson Type</label>
                <select id="lessonType" class="agy-input">
                    <option value="video">Video (YouTube/Upload)</option>
                    <option value="document">Document (PDF)</option>
                    <option value="text">Article / Text</option>
                    <option value="quiz">Quiz / Evaluation</option>
                </select>
            </div>
        </div>
        <div class="agy-modal-footer">
            <button class="agy-btn agy-btn-secondary" onclick="closeLessonModal()">Cancel</button>
            <button class="agy-btn agy-btn-primary" onclick="saveLesson()">Save Lesson</button>
        </div>
    </div>
</div>


<!-- Section Modal -->
<div class="agy-modal-backdrop" id="sectionModal">
    <div class="agy-modal">
        <div class="agy-modal-header">
            <h3 class="agy-modal-title" id="sectionModalTitle">Add New Section</h3>
            <button class="agy-modal-close" onclick="closeSectionModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="agy-modal-body">
            <input type="hidden" id="editSectionId">
            <label class="agy-label text-dark fw-bold mb-2">Section Title</label>
            <input type="text" id="sectionTitle" class="agy-input" placeholder="e.g. Introduction, Module 1, etc.">
        </div>
        <div class="agy-modal-footer">
            <button class="agy-btn agy-btn-secondary" onclick="closeSectionModal()">Cancel</button>
            <button class="agy-btn agy-btn-primary" onclick="saveSection()">Save Section</button>
        </div>
    </div>
</div>

<script>
    const courseId = '<?= $course['id'] ?>';
    
        function openLessonModal(sectionId) {
        document.getElementById('lessonTitle').value = '';
        document.getElementById('lessonType').value = 'video';
        document.getElementById('activeSectionId').value = sectionId;
        const modal = document.getElementById('lessonModal');
        modal.classList.add('show');
    }
    
    function closeLessonModal() {
        document.getElementById('lessonModal').classList.remove('show');
    }
    
    async function saveLesson() {
        const title = document.getElementById('lessonTitle').value;
        const type = document.getElementById('lessonType').value;
        const sectionId = document.getElementById('activeSectionId').value;
        
        if(!title.trim()) {
            AgyToast.error("Lesson title cannot be empty!");
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('section_id', sectionId);
            formData.append('title', title);
            formData.append('type', type);
            
            const res = await fetch('/admin/program/lesson/add', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.status === 'success') {
                closeLessonModal();
                AgyToast.success(result.message);
                
                // Get icon based on type
                let iconClass = 'fa-play';
                if(type === 'document') iconClass = 'fa-file-pdf';
                if(type === 'text') iconClass = 'fa-file-alt';
                if(type === 'quiz') iconClass = 'fa-tasks';
                
                const html = `
                <div class="lesson-item">
                    <div class="d-flex align-items-center">
                        <div class="lesson-icon">
                            <i class="fas ${iconClass}"></i>
                        </div>
                        <div class="fw-bold text-dark">${result.data.title}</div>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-light text-muted border" onclick="window.location.href='/admin/program/lesson/editor/' + result.data.id"><i class="fas fa-cog"></i></button>
                    </div>
                </div>`;
                
                // Find the button we clicked
                const addButton = document.querySelector(`button[onclick="openLessonModal('${sectionId}')"]`);
                
                // If there is a "No lessons" message, remove it
                const noLessonMsg = addButton.parentElement.previousElementSibling;
                if(noLessonMsg && noLessonMsg.innerHTML.includes('No lessons')) {
                    noLessonMsg.remove();
                }
                
                // Insert before the Add Lesson button container
                addButton.parentElement.insertAdjacentHTML('beforebegin', html);
                
            } else {
                AgyToast.error(result.message);
            }
        } catch (e) {
            AgyToast.error("Failed to connect to server.");
        }
    }


    function openSectionModal() {
        document.getElementById('sectionTitle').value = '';
        const modal = document.getElementById('sectionModal');
        modal.classList.add('show');
    }
    
    function closeSectionModal() {
        document.getElementById('sectionModal').classList.remove('show');
    }
    
    async function saveSection() {
        const title = document.getElementById('sectionTitle').value;
        if(!title.trim()) {
            AgyToast.error("Title cannot be empty!");
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('course_id', courseId);
            formData.append('title', title);
            
            const res = await fetch('/admin/program/section/add', {
                method: 'POST',
                body: formData
            });
            const result = await res.json();
            
            if (result.status === 'success') {
                closeSectionModal();
                AgyToast.success(result.message);
                
                // Add to UI automatically
                const container = document.getElementById('curriculum-container');
                const emptyState = document.getElementById('empty-curriculum-state');
                if(emptyState) emptyState.remove();
                
                const html = `
                <div class="curriculum-section">
                    <div class="curriculum-section-header">
                        <h4 class="curriculum-section-title">${result.data.title}</h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-light border"><i class="fas fa-edit"></i></button>
                            <button class="btn btn-sm btn-light border text-danger"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="curriculum-section-body">
                        <div class="p-4 text-center text-muted" style="font-size: 0.9rem;">
                            No lessons in this section yet.
                        </div>
                        <div class="p-3 bg-light border-top">
                            <button class="btn btn-sm btn-outline-secondary w-100" style="border-style: dashed;" onclick="openLessonModal('${result.data.id}')">
                                <i class="fas fa-plus"></i> Add Lesson
                            </button>
                        </div>
                    </div>
                </div>`;
                container.insertAdjacentHTML('beforeend', html);
            } else {
                AgyToast.error(result.message);
            }
        } catch (e) {
            AgyToast.error("Failed to connect to server.");
        }
    }

    function openEditSectionModal(id, title) {
        document.getElementById('sectionModalTitle').innerText = 'Edit Section';
        document.getElementById('editSectionId').value = id;
        document.getElementById('sectionTitle').value = title;
        document.getElementById('sectionModal').classList.add('show');
    }
    
    // Override openSectionModal to clear ID
    function openSectionModal() {
        document.getElementById('sectionModalTitle').innerText = 'Add New Section';
        document.getElementById('editSectionId').value = '';
        document.getElementById('sectionTitle').value = '';
        document.getElementById('sectionModal').classList.add('show');
    }
    
    // Override saveSection to handle update
    async function saveSection() {
        const title = document.getElementById('sectionTitle').value;
        const editId = document.getElementById('editSectionId').value;
        
        if(!title.trim()) { AgyToast.error('Title required!'); return; }
        
        try {
            const formData = new FormData();
            formData.append('title', title);
            
            let url = '/admin/program/section/add';
            if (editId) {
                url = '/admin/program/section/update';
                formData.append('id', editId);
            } else {
                formData.append('course_id', courseId);
            }
            
            const res = await fetch(url, { method: 'POST', body: formData });
            const result = await res.json();
            
            if (result.status === 'success') {
                closeSectionModal();
                AgyToast.success(result.message);
                if (editId) {
                    setTimeout(() => window.location.reload(), 1000); // Simple reload for update
                } else {
                    setTimeout(() => window.location.reload(), 1000); // Also reload for add to get proper ID bindings easily
                }
            } else {
                AgyToast.error(result.message);
            }
        } catch (e) { AgyToast.error('Network error'); }
    }
    
    async function deleteSection(id, btn) {
        const confirmed = await AgyConfirm.show('Hapus Section?', 'Tindakan ini tidak bisa dibatalkan dan semua lesson di dalamnya akan ikut terhapus.', 'danger', 'Ya, Hapus');
        if(!confirmed) return;
        try {
            const res = await fetch('/admin/program/section/delete/' + id, {method: 'POST'});
            const result = await res.json();
            if(result.status === 'success') {
                AgyToast.success(result.message);
                btn.closest('.curriculum-section').remove();
            } else { AgyToast.error(result.message); }
        } catch(e) { AgyToast.error('Network error'); }
    }
    
    async function deleteLesson(id, btn) {
        const confirmed = await AgyConfirm.show('Hapus Lesson?', 'Apakah Anda yakin ingin menghapus materi ini secara permanen?', 'danger', 'Ya, Hapus');
        if(!confirmed) return;
        try {
            const res = await fetch('/admin/program/lesson/delete/' + id, {method: 'POST'});
            const result = await res.json();
            if(result.status === 'success') {
                AgyToast.success(result.message);
                btn.closest('.lesson-item').remove();
            } else { AgyToast.error(result.message); }
        } catch(e) { AgyToast.error('Network error'); }
    }

</script>

<?= $this->endSection() ?>




