<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>

<style>
    /* B2B Premium CRM Style - Editor View */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; font-weight: 800; letter-spacing: -0.02em; }
    
    .agy-label { font-size: 0.85rem; color: #475569; font-weight: 700; margin-bottom: 8px; display: block; }
    
    /* Buttons */
    .agy-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 12px 24px; border-radius: 99px; font-weight: 700; font-size: 0.95rem;
        font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: all 0.2s ease;
        border: none; outline: none; text-decoration: none; gap: 8px;
    }
    .agy-btn-primary { background: #0f172a; color: white; }
    .agy-btn-primary:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); color: white; }
    .agy-btn-back { color: #64748b; font-weight: 600; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s; }
    .agy-btn-back:hover { color: #0f172a; }
    
    /* Custom Inputs */
    .agy-input, .agy-textarea {
        width: 100%; padding: 14px 16px; border: 1px solid #e2e8f0; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; color: #0f172a;
        background: #f8fafc; transition: all 0.2s; outline: none;
    }
    .agy-input:focus, .agy-textarea:focus { background: #fff; border-color: #94a3b8; box-shadow: 0 0 0 4px #f1f5f9; }
    
    /* File Upload Area */
    .upload-area {
        border: 2px dashed #cbd5e1; border-radius: 16px; padding: 48px 32px;
        text-align: center; background: #f8fafc; transition: all 0.2s; cursor: pointer;
    }
    .upload-area:hover { border-color: #94a3b8; background: #f1f5f9; }
    .upload-icon { font-size: 3rem; color: #94a3b8; margin-bottom: 16px; }
    .file-input { display: none; }
    
    .editor-container { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 40px; }
</style>

<div class="container-fluid p-0">
    
    <!-- Top Header Navigation -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="/admin/program/course/details/<?= $course_id ?>" class="agy-btn-back">
            <i class="fas fa-arrow-left"></i> Back to Curriculum
        </a>
    </div>

    <!-- Main Title -->
    <div class="mb-5">
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                <?= esc(strtoupper($lesson['type'])) ?> LESSON
            </span>
            <span class="text-muted fw-bold" style="font-size: 0.85rem;"><i class="fas fa-folder-open text-primary me-2"></i> <?= esc($section_title) ?></span>
        </div>
        <h1 class="display-6 fw-bold text-dark mb-0">Edit Lesson</h1>
    </div>

    <div class="row g-0"><div class="col-lg-8"><div class="editor-container">
        <form action="/admin/program/lesson/save" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $lesson['id'] ?>">
            
            <div class="mb-4">
                <label class="agy-label">Lesson Title</label>
                <input type="text" name="title" class="agy-input" value="<?= esc($lesson['title']) ?>" required>
            </div>
            
            <div class="mb-4">
                <label class="agy-label d-flex align-items-center gap-2">
                    <input type="checkbox" name="is_free" value="1" <?= $lesson['is_free'] ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                    Make this lesson FREE (Preview)
                </label>
                <div class="text-muted" style="font-size: 0.8rem; margin-left: 26px;">Users can view this lesson without purchasing the course.</div>
            </div>
            
            <hr class="my-5" style="border-color: #e2e8f0; opacity: 1;">
            
            <h4 class="fw-bold mb-4">Lesson Content</h4>
            
            <?php if($lesson['type'] === 'video'): ?>
                
                <div class="mb-4">
                    <label class="agy-label">Video URL (YouTube / Vimeo)</label>
                    <input type="url" name="content" class="agy-input" placeholder="https://www.youtube.com/watch?v=..." value="<?= strpos($lesson['content'], 'http') === 0 ? esc($lesson['content']) : '' ?>">
                </div>
                
                <div class="text-center text-muted fw-bold mb-4">OR</div>
                
                <div class="mb-4">
                    <label class="agy-label">Upload Video File (.mp4)</label>
                    <label class="upload-area w-100 d-block">
                        <i class="fas fa-cloud-upload-alt upload-icon"></i>
                        <h5 class="fw-bold text-dark">Click to browse or drag video here</h5>
                        <p class="text-muted mb-0">Maximum file size: 100MB</p>
                        <input type="file" name="file_content" class="file-input" accept="video/mp4" onchange="document.getElementById('fileName').innerText = this.files[0].name">
                    </label>
                    <div id="fileName" class="text-primary fw-bold mt-2 text-center"></div>
                </div>
                
            <?php elseif($lesson['type'] === 'document'): ?>
            
                <div class="mb-4">
                    <label class="agy-label">Upload PDF Document</label>
                    <label class="upload-area w-100 d-block">
                        <i class="fas fa-file-pdf upload-icon text-danger"></i>
                        <h5 class="fw-bold text-dark">Click to browse or drag PDF here</h5>
                        <p class="text-muted mb-0">Maximum file size: 20MB</p>
                        <input type="file" name="file_content" class="file-input" accept="application/pdf" onchange="document.getElementById('fileName').innerText = this.files[0].name">
                    </label>
                    <div id="fileName" class="text-primary fw-bold mt-2 text-center"></div>
                </div>
            
            <?php elseif($lesson['type'] === 'text'): ?>
            
                <div class="mb-4">
                    <label class="agy-label">Article Content</label>
                    <textarea name="content" class="agy-textarea" rows="15" placeholder="Write your lesson content here..."><?= esc($lesson['content']) ?></textarea>
                    <div class="text-muted mt-2" style="font-size: 0.8rem;">You can write raw HTML or standard text. Rich text editor will be implemented in a future update.</div>
                </div>
            
            <?php endif; ?>
            
            <?php if(!empty($lesson['content']) && strpos($lesson['content'], 'uploads/') !== false): ?>
                <div class="p-3 bg-light rounded-3 mb-4 d-flex align-items-center justify-content-between border">
                    <div class="d-flex align-items-center gap-3">
                        <i class="fas fa-check-circle text-success fs-4"></i>
                        <div>
                            <div class="fw-bold text-dark">File currently uploaded:</div>
                            <div class="text-muted" style="font-size: 0.85rem;"><?= esc(basename($lesson['content'])) ?></div>
                        </div>
                    </div>
                    <a href="/<?= esc($lesson['content']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3">View File</a>
                </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-end mt-5 pt-4 border-top">
                <button type="submit" class="agy-btn agy-btn-primary px-5">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
            
        </form>
    </div></div></div>
</div>

<?= $this->endSection() ?>
