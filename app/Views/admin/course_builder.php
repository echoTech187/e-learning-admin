<?= $this->extend('layouts/dashboard') ?>
<?= $this->section('content') ?>

<!-- Choices.js CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<!-- Choices.js JS -->
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js">
    // Initialize Choices.js on all select elements
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.agy-select');
        selects.forEach(function(select) {
            new Choices(select, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });
    });
</script>


<style>
    /* Typography */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; font-weight: 800; letter-spacing: -0.02em; }
    .agy-label { font-size: 0.8rem; color: #475569; font-weight: 600; margin-bottom: 8px; display: block; }
    
    /* Premium Solid Inputs */
    .agy-input, .agy-textarea { 
        width: 100%;
        background-color: #f8fafc; 
        border: 1px solid #e2e8f0; 
        padding: 14px 16px; 
        border-radius: 12px; 
        font-size: 0.95rem; 
        color: #0f172a; 
        font-weight: 500;
        font-family: 'Plus Jakarta Sans', sans-serif;
        transition: all 0.2s ease; 
        box-sizing: border-box;
    }
    .agy-textarea { resize: vertical; min-height: 120px; }
    .agy-input::placeholder, .agy-textarea::placeholder { color: #94a3b8; font-weight: 400; }
    .agy-input:focus, .agy-textarea:focus { 
        background-color: #ffffff;
        border-color: #0f172a; 
        box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.05); 
        outline: none;
    }
    
    /* Remove number arrows */
    .agy-input[type="number"]::-webkit-inner-spin-button, 
    .agy-input[type="number"]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .agy-input[type="number"] { -moz-appearance: textfield; }
    
    /* Choices.js Custom Overrides for Premium Look */
    .choices { margin-bottom: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
    .choices__inner {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        padding: 6px 16px !important;
        min-height: 50px !important;
        display: flex; align-items: center;
        transition: all 0.2s ease;
    }
    .choices.is-open .choices__inner {
        background-color: #ffffff !important;
        border-color: #0f172a !important;
        border-radius: 12px !important;
        box-shadow: 0 0 0 4px rgba(15, 23, 42, 0.05) !important;
    }
    .choices__list--dropdown {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1) !important;
        margin-top: 8px !important;
        padding: 8px !important;
        z-index: 99 !important;
    }
    .choices__list--dropdown .choices__item {
        border-radius: 8px; padding: 10px 16px !important; font-size: 0.95rem; font-weight: 500; color: #475569;
    }
    .choices__list--dropdown .choices__item--selectable.is-highlighted {
        background-color: #f1f5f9 !important; color: #0f172a !important;
    }
    .choices[data-type*="select-one"]::after {
        border: none !important; margin-top: 0 !important;
        width: 20px; height: 20px; right: 16px; top: 50%; transform: translateY(-50%);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23475569' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        background-size: 12px 12px; background-repeat: no-repeat; background-position: center;
        transition: transform 0.2s ease;
    }
    .choices.is-open[data-type*="select-one"]::after { transform: translateY(-50%) rotate(180deg); }
    
    /* Buttons */
    .agy-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 12px 28px; border-radius: 99px; font-weight: 700; font-size: 0.95rem;
        font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: all 0.2s ease;
        border: none; outline: none; text-decoration: none; gap: 8px;
    }
    .agy-btn-primary { background: #0f172a; color: white; }
    .agy-btn-primary:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); }
    .agy-btn-secondary { background: #f8fafc; color: #0f172a; border: 1px solid #e2e8f0; }
    .agy-btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; }
    .agy-btn-back { color: #64748b; font-weight: 600; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s; }
    .agy-btn-back:hover { color: #0f172a; }
    
    /* Upload Zone */
    .agy-upload-zone {
        background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 16px;
        transition: all 0.2s ease; cursor: pointer; padding: 48px; text-align: center;
    }
    .agy-upload-zone:hover { background: #f1f5f9; border-color: #94a3b8; }
    .agy-upload-icon-wrapper {
        display: inline-flex; align-items: center; justify-content: center;
        background: white; border-radius: 50%; width: 64px; height: 64px;
        color: #475569; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease;
    }
    .agy-upload-zone:hover .agy-upload-icon-wrapper { transform: scale(1.05); color: #0f172a; }

    /* Divider Lines */
    .divider-right { border-right: 1px solid #e2e8f0; }
    @media (max-width: 991.98px) { .divider-right { border-right: none; border-bottom: 1px solid #e2e8f0; } }
    .section-title { font-size: 1.25rem; margin-bottom: 32px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; }
</style>

<!-- Choices.js Library -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js">
    // Initialize Choices.js on all select elements
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.agy-select');
        selects.forEach(function(select) {
            new Choices(select, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });
    });
</script>

<div class="container-fluid" style="max-width: 1400px; padding: 0 16px;">
    
    <form action="/admin/program/course/save" method="POST" enctype="multipart/form-data" novalidate>
        <input type="hidden" name="id" value="<?= isset($course) ? $course['id'] : '' ?>">
        <?php if(isset($course)): ?>
            <input type="hidden" name="old_thumbnail" value="<?= esc($course['thumbnail']) ?>">
        <?php endif; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-end mb-4 border-bottom pb-4 pt-2">
            <div>
                <a href="/admin/program" class="agy-btn-back mb-3"><i class="fas fa-chevron-left" style="font-size: 10px;"></i> Back to Programs</a>
                
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="agy-btn agy-btn-secondary" onclick="window.history.back()">Discard</button>
                <button type="submit" class="agy-btn agy-btn-primary">Save Course</button>
            </div>
        </div>

        <div class="row g-0">
            <!-- Left Column: Details -->
            <div class="col-lg-8 divider-right pe-lg-5 pb-5">
                
                <h4 class="section-title">Customer details <span class="text-muted fw-normal fs-6 ms-2">/ General info</span></h4>
                
                <div class="row g-4 mb-5">
                    <div class="col-12">
                        <label class="agy-label">Course Title</label>
                        <input type="text" class="agy-input" name="title" required value="<?= isset($course) ? esc($course['title']) : '' ?>" placeholder="e.g. Masterclass in B2B Sales">
                    </div>
                    <div class="col-12">
                        <label class="agy-label">Description</label>
                        <textarea class="agy-textarea" name="description" placeholder="Write a compelling description..."><?= isset($course) ? esc($course['description']) : '' ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="agy-label">Category</label>
                        <select class="agy-select" name="category_id">
                            <option value="">Select Category</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (isset($course) && $course['category_id'] == $cat['id']) ? 'selected' : '' ?>><?= esc($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="agy-label">Instructor</label>
                        <select class="agy-select" name="instructor_id">
                            <option value="">Select Instructor</option>
                            <?php foreach($instructors as $inst): ?>
                                <option value="<?= $inst['id'] ?>" <?= (isset($course) && $course['instructor_id'] == $inst['id']) ? 'selected' : '' ?>><?= esc($inst['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <h4 class="section-title mt-5">Media <span class="text-muted fw-normal fs-6 ms-2">/ Thumbnail</span></h4>
                
                <div class="agy-upload-zone" onclick="document.getElementById('thumbnailInput').click()">
                    <?php if(isset($course) && $course['thumbnail']): ?>
                        <img id="previewImage" src="/<?= esc($course['thumbnail']) ?>" class="img-fluid rounded-2 mb-3" style="max-height: 300px; width: 100%; object-fit: cover;">
                        <div id="uploadStateGroup" class="d-none">
                            <div class="agy-upload-icon-wrapper">
                                <i class="fas fa-cloud-upload-alt fs-5" id="uploadIcon"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Upload a new cover image</h6>
                            <div class="small text-muted mb-0">SVG, PNG, or JPG (max. 2MB)</div>
                        </div>
                    <?php else: ?>
                        <img id="previewImage" src="" class="img-fluid rounded-2 mb-3 d-none" style="max-height: 300px; width: 100%; object-fit: cover;">
                        <div id="uploadStateGroup">
                            <div class="agy-upload-icon-wrapper">
                                <i class="fas fa-image fs-5" id="uploadIcon"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">Click to upload cover image</h6>
                            <div class="small text-muted mb-0">Recommended ratio 16:9, up to 2MB</div>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="d-none" name="thumbnail" id="thumbnailInput" accept="image/*">
                </div>
            </div>
            
            <!-- Right Column: Settings -->
            <div class="col-lg-4 ps-lg-5 pt-5 pt-lg-0">
                <div class="position-sticky" style="top: 24px;">
                    <h4 class="section-title">Configuration</h4>
                    
                    <div class="d-flex flex-column gap-4">
                        <div>
                            <label class="agy-label">Price (IDR)</label>
                            <input type="number" class="agy-input" name="price" value="<?= isset($course) ? esc($course['price']) : '0' ?>">
                            <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">Set to 0 for a free course.</small>
                        </div>
                        
                        <div>
                            <label class="agy-label">Difficulty Level</label>
                            <select class="agy-select" name="level">
                                <option value="beginner" <?= (isset($course) && $course['level'] == 'beginner') ? 'selected' : '' ?>>Beginner</option>
                                <option value="intermediate" <?= (isset($course) && $course['level'] == 'intermediate') ? 'selected' : '' ?>>Intermediate</option>
                                <option value="advanced" <?= (isset($course) && $course['level'] == 'advanced') ? 'selected' : '' ?>>Advanced</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="agy-label">Publish Status</label>
                            <select class="agy-select" name="status">
                                <option value="draft" <?= (isset($course) && $course['status'] == 'draft') ? 'selected' : '' ?>>Draft</option>
                                <option value="published" <?= (isset($course) && $course['status'] == 'published') ? 'selected' : '' ?>>Published</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.getElementById('thumbnailInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById('previewImage');
                img.src = e.target.result;
                img.classList.remove('d-none');
                
                const uploadGroup = document.getElementById('uploadStateGroup');
                if (uploadGroup) uploadGroup.classList.add('d-none');
            }
            reader.readAsDataURL(file);
        }
    });

    // Initialize Choices.js on all select elements
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.agy-select');
        selects.forEach(function(select) {
            new Choices(select, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });
    });
</script>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.agy-select');
        selects.forEach(function(select) {
            new Choices(select, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });
    });

    // Initialize Choices.js on all select elements
    document.addEventListener('DOMContentLoaded', function() {
        const selects = document.querySelectorAll('.agy-select');
        selects.forEach(function(select) {
            new Choices(select, {
                searchEnabled: false,
                itemSelectText: '',
                shouldSort: false
            });
        });
    });
</script>

<?= $this->endSection() ?>



