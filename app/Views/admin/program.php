<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>
<style>
    /* ULTRA-MINIMALIST B2B STYLES (Ref: Image 1) */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #000; font-weight: 800; letter-spacing: -0.03em; }
    .text-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: #6b7280; font-weight: 600; }
    
    .flat-tabs { display: flex; gap: 32px; border-bottom: 1px solid #d1d5db; margin-bottom: 32px; padding: 0; }
    .flat-tab { padding: 0 0 8px 0; color: #9ca3af; font-weight: 500; font-size: 0.85rem; cursor: pointer; position: relative; background: none; border: none; margin-bottom: -1px; }
    .flat-tab.active { color: #111827; font-weight: 600; }
    .flat-tab:hover { color: #374151; background-color: transparent !important; }
    .flat-tab:focus, .flat-tab:active { outline: none !important; box-shadow: none !important; background-color: transparent !important; }
    .flat-tab.active::after { content: ''; position: absolute; bottom: -6px; left: 50%; margin-left: -5px; width: 10px; height: 10px; background: #ffffff; border-bottom: 1px solid #d1d5db; border-right: 1px solid #d1d5db; border-top: none; border-left: none; transform: rotate(45deg); z-index: 10; border-radius: 1px; }
    
    .pill-btn { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 0.85rem; color: #374151; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; }
    .pill-btn:hover { background: #f9fafb; border-color: #9ca3af; }

    /* Category Seamless List */
    .cat-item {
        padding: 24px 0;
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s ease;
    }
    .cat-item:hover {
        /* No background change to keep it ultra-minimalist */
    }
    .cat-icon-wrapper {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6; /* Soft gray like the Live chat icon in Ref */
        color: #000;
        font-size: 1.2rem;
    }
    .cat-child-list {
        margin-left: 64px;
        padding-top: 16px;
    }
    .cat-child-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px dashed #f3f4f6;
    }
    .cat-child-item:last-child {
        border-bottom: none;
    }

    /* Course Cards Grid (Seamless) */
    .course-card {
        background: white;
        border: 1px solid #f3f4f6;
        border-radius: 16px;
        padding: 16px;
        transition: all 0.2s ease;
    }
    .course-card:hover {
        border-color: #d1d5db;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .course-thumb {
        height: 200px;
        background: #e5e7eb;
        position: relative;
        border-radius: 12px;
        overflow: hidden;
    }
    .course-thumb::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 40%;
        background: linear-gradient(to top, rgba(0,0,0,0.4), transparent);
    }
    .course-level-badge {
        position: absolute;
        top: 12px;
        right: 12px;
        background: #fff;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 1px;
        color: #000;
        z-index: 2;
    }
    
    /* Grid layout */
    .grid-container { display: grid; grid-template-columns: 1fr 1fr; gap: 48px; }
    
    .btn-action-text {
        background: none; border: none; color: #000; font-weight: 600; font-size: 0.85rem; padding: 0; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-action-text:hover { text-decoration: underline; }
</style>

<div class="d-flex justify-content-between align-items-end mb-4">
    <div>
        <h1 class="mb-2" style="font-size: 2.5rem;">Program management</h1>
        <p class="text-muted fw-medium mb-0">Manage course catalogs and curriculum structures.</p>
    </div>
    <div class="d-flex gap-3">
        <button class="pill-btn" onclick="AgyToast.info('Advanced filtering is currently under development for Phase 4.')"><i class="fas fa-filter"></i> Filter</button>
        <button class="btn btn-dark rounded-3 px-4 fw-bold" id="btnAddCategory" onclick="openCategoryModal()"><i class="fas fa-plus me-2"></i> Add category</button>
          <a href="/admin/program/course/builder" class="btn btn-dark rounded-3 px-4 fw-bold d-none" id="btnAddCourse"><i class="fas fa-plus me-2"></i> Add course</a>
    </div>
</div>

<div class="flat-tabs mb-4" id="programTabs" role="tablist">
    <button class="flat-tab active" id="kategori-tab"  data-bs-target="#kategori-tab-pane" type="button" role="tab">Categories</button>
    <button class="flat-tab" id="kursus-tab"  data-bs-target="#kursus-tab-pane" type="button" role="tab">All courses</button>
</div>

<div class="tab-content" id="programTabsContent">
    <!-- Tab Kategori -->
    <div class="tab-pane fade show active" id="kategori-tab-pane" role="tabpanel">
        
        <?php if (empty($categories)): ?>
            <div class="text-center py-4">
                <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-50"></i>
                <h4 class="fw-bold text-dark">No categories found</h4>
                <p class="text-muted">Create your first category structure.</p>
            </div>
        <?php else: ?>
            <div class="grid-container">
                <div>
                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                        <span class="text-label">Structure</span>
                        <span class="text-label">Actions</span>
                    </div>
                    
                    <?php foreach ($categories as $cat): ?>
                    <div class="cat-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="cat-icon-wrapper">
                                    <?php 
$iconClass = "fa-folder";
$iconColor = "";
if (!empty($cat["icon"])) {
    $iconData = json_decode($cat["icon"], true);
    if (json_last_error() === JSON_ERROR_NONE && isset($iconData["icon"])) {
        $iconClass = $iconData["icon"];
        $iconColor = isset($iconData["color"]) ? $iconData["color"] : "";
    } else {
        $iconClass = $cat["icon"];
    }
}
$iconClass = strpos($iconClass, "fa-") === 0 ? "fas " . $iconClass : $iconClass;
?>
<i class="<?= esc($iconClass) ?>" style="color: <?= esc($iconColor) ?>;"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size: 1.1rem;"><?= esc($cat['name']) ?></div>
                                    <div class="text-muted small"><?= esc($cat['description']) ?></div>
                                </div>
                            </div>
                            <div>
                                <button class="btn-action-text me-3" onclick="editCategory('<?= $cat['id'] ?>')" ><i class="far fa-edit"></i> Edit</button> <button class="btn-action-text text-danger" onclick="deleteCategory('<?= $cat['id'] ?>')" ><i class="far fa-trash-alt"></i> Delete</button>
                            </div>
                        </div>

                        <?php if (!empty($cat['children'])): ?>
                            <div class="cat-child-list">
                                <?php foreach ($cat['children'] as $child): ?>
                                <div class="cat-child-item">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="timeline-dot" style="background: #000; width: 6px; height: 6px;"></div>
                                        <span class="fw-semibold text-dark"><?= esc($child['name']) ?></span>
                                    </div>
                                    <div>
                                          <button class="btn-action-text text-muted me-3" style="font-size: 0.8rem;" onclick="editCategory('<?= $child['id'] ?>')" ><i class="far fa-edit"></i> Edit</button>
                                          <button class="btn-action-text text-danger" style="font-size: 0.8rem;" onclick="deleteCategory('<?= $child['id'] ?>')" ><i class="far fa-trash-alt"></i> Delete</button>
                                      </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div>
                    <h3 class="mb-4">Category profile</h3>
                    <p class="text-muted fw-bold d-flex align-items-center gap-2 mb-4"><i class="fas fa-trophy"></i> Hierarchy setup</p>
                    
                    <div class="row mb-5">
                        <div class="col-6">
                            <div class="text-label mb-1">Max Depth</div>
                            <div class="fw-bold">2 Levels</div>
                        </div>
                        <div class="col-6">
                            <div class="text-label mb-1">Total Active</div>
                            <div class="fw-bold"><?= count($categories) ?> Parent Categories</div>
                        </div>
                    </div>
                    
                    <div class="mb-5">
                        <div class="text-label mb-1">Taxonomy Rules</div>
                        <div class="fw-bold">B2B Standards Applied</div>
                    </div>
                    
                    <h4 class="mb-4 d-flex justify-content-between align-items-center">
                        Guidelines <i class="fas fa-arrow-right text-muted" style="font-size: 1rem;"></i>
                    </h4>
                    
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: #fdf6b2;">
                            <i class="fas fa-lightbulb text-warning fs-4"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Specific naming</div>
                            <div class="text-muted small">Enhances search UX significantly.</div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Tab Kursus -->
    <div class="tab-pane fade" id="kursus-tab-pane" role="tabpanel">
        
        <?php if (empty($courses)): ?>
            <div class="text-center py-4">
                <i class="fas fa-book-open fa-3x text-muted mb-3 opacity-50"></i>
                <h4 class="fw-bold text-dark">No courses found</h4>
                <p class="text-muted">Start creating your curriculum.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($courses as $c): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="course-card h-100 d-flex flex-column">
                        <div class="course-thumb mb-3" style="background-image: url('<?= esc($c['thumbnail'] ?: 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600&auto=format&fit=crop') ?>'); background-size: cover; background-position: center;">
                            <span class="course-level-badge"><?= esc(strtoupper($c['level'])) ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-label" style="font-size: 0.65rem;"><i class="fas fa-tag me-1"></i> <?= esc($c['category_name']) ?></span>
                            <?php if ($c['status'] == 'published'): ?>
                                <span class="fw-bold text-success" style="font-size: 0.75rem;"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> PUBLISHED</span>
                            <?php else: ?>
                                <span class="fw-bold text-warning" style="font-size: 0.75rem;"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> DRAFT</span>
                            <?php endif; ?>
                        </div>
                        
                        <h5 class="fw-bold mb-3 text-dark" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                            <?= esc($c['title']) ?>
                        </h5>
                        
                        <div class="d-flex align-items-center mb-4">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($c['instructor_name']) ?>&background=f3f4f6&color=000&rounded=true" style="width: 24px; height: 24px; border-radius: 50%;" class="me-2">
                            <span class="text-muted fw-bold" style="font-size: 0.85rem;"><?= esc($c['instructor_name']) ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center pt-2 mt-auto">
                            <div class="fw-bold fs-5">
                                <?php if ($c['price'] == 0): ?>
                                    Free
                                <?php else: ?>
                                    Rp <?= number_format($c['price'], 0, ',', '.') ?>
                                <?php endif; ?>
                            </div>
                            <button class="btn-action-text" onclick="window.location.href='/admin/program/course/details/<?= $c['id'] ?>'">Details <i class="fas fa-arrow-right"></i></button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div id="load-more-trigger" class="py-4 text-center text-muted d-flex align-items-center justify-content-center gap-2">
                 <i class="fas fa-circle-notch fa-spin d-none" id="load-more-spinner"></i>
                 <span id="load-more-text" style="font-size: 0.85rem; font-weight: 600;">Scroll for more courses</span>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>

    

document.addEventListener("DOMContentLoaded", function() {
    const grid = document.getElementById("course-grid");
    const trigger = document.getElementById("load-more-trigger");
    const spinner = document.getElementById("load-more-spinner");
    const text = document.getElementById("load-more-text");
    
    if(!grid || !trigger) return;

    let offset = 12;
    let isLoading = false;
    let hasMore = true;

    const observer = new IntersectionObserver((entries) => {
        if(entries[0].isIntersecting && !isLoading && hasMore) {
            loadMore();
        }
    }, { rootMargin: "100px" });

    observer.observe(trigger);

    async function loadMore() {
        isLoading = true;
        spinner.classList.remove("d-none");
        text.innerText = "Loading...";

        try {
            const res = await fetch(`/admin/program/load-more?offset=${offset}`);
            const json = await res.json();
            
            if(json.data && json.data.length > 0) {
                json.data.forEach(c => {
                    const statusHtml = c.status === "published" 
                        ? `<span class="fw-bold text-success" style="font-size: 0.75rem;"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> PUBLISHED</span>`
                        : `<span class="fw-bold text-warning" style="font-size: 0.75rem;"><i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> DRAFT</span>`;
                        
                    const html = `
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="course-card h-100 d-flex flex-column">
                            <div class="course-thumb mb-3" style="background-image: url('${c.thumbnail}'); background-size: cover; background-position: center;">
                                <span class="course-level-badge">${c.level}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-label" style="font-size: 0.65rem;"><i class="fas fa-tag me-1"></i> ${c.category_name}</span>
                                ${statusHtml}
                            </div>
                            <h5 class="fw-bold mb-3 text-dark" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;">
                                ${c.title}
                            </h5>
                            <div class="d-flex align-items-center mb-4">
                                <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(c.instructor_name)}&background=f3f4f6&color=000&rounded=true" style="width: 24px; height: 24px; border-radius: 50%;" class="me-2">
                                <span class="text-muted fw-bold" style="font-size: 0.85rem;">${c.instructor_name}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 mt-auto">
                                <div class="fw-bold fs-5">${c.price_formatted}</div>
                                <button class="btn-action-text" onclick="window.location.href='/admin/program/course/details/${c.id}'">Details <i class="fas fa-arrow-right"></i></button>
                            </div>
                        </div>
                    </div>`;
                    grid.insertAdjacentHTML("beforeend", html);
                });
                
                offset += 12;
                if(json.data.length < 12) {
                    hasMore = false;
                    text.innerText = "End of results";
                    spinner.classList.add("d-none");
                } else {
                    text.innerText = "Scroll for more courses";
                    spinner.classList.add("d-none");
                }
            } else {
                hasMore = false;
                text.innerText = "End of results";
                spinner.classList.add("d-none");
            }
        } catch (e) {
            console.error(e);
            text.innerText = "Error loading data"; AgyToast.error('Error', 'Error loading data', 'error');
            spinner.classList.add("d-none");
        }
        isLoading = false;
    }
});
</script>

<!-- Category Modal -->
<div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border: none; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
      <div class="modal-header" style="border-bottom: 1px solid #f3f4f6; padding: 24px;">
        <h5 class="modal-title fw-bold" id="categoryModalTitle">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="categoryForm">
        <div class="modal-body" style="padding: 24px;">
            <input type="hidden" id="cat_id" name="id">
            
            <div class="mb-4">
                <label class="text-label mb-2 d-block">Category Name</label>
                <input type="text" class="form-control p-3 bg-light border-0 rounded-3" id="cat_name" name="name" required placeholder="e.g. Digital Marketing">
            </div>
            
            <div class="mb-4">
                <label class="text-label mb-2 d-block">Description</label>
                <textarea class="form-control p-3 bg-light border-0 rounded-3" id="cat_desc" name="description" rows="3" placeholder="Brief description of this category"></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label class="text-label mb-2 d-block">Parent Category</label>
                    <select class="form-select p-3 bg-light border-0 rounded-3" id="cat_parent" name="parent_id">
                        <option value="">None (Top Level)</option>
                        <?php foreach($flat_categories as $fc): ?>
                            <option value="<?= $fc['id'] ?>"><?= esc($fc['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 mb-4">
                    <label class="text-label mb-2 d-block">Icon (FontAwesome)</label>
                    <input type="text" class="form-control p-3 bg-light border-0 rounded-3" id="cat_icon" name="icon" placeholder="e.g. fa-laptop">
                </div>
            </div>
        </div>
        <div class="modal-footer" style="border-top: 1px solid #f3f4f6; padding: 24px;">
            <button type="button" class="btn btn-light rounded-3 px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark rounded-3 px-4 fw-bold" id="btnSaveCat">Save Category</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>

    

let categoryModal;
let categoryForm;


    const tabs = document.querySelectorAll('.flat-tab');
    const panes = document.querySelectorAll('.tab-pane');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            
            tabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            panes.forEach(p => {
                p.classList.remove('show', 'active');
            });
            
            const targetId = this.getAttribute('data-bs-target');
            const targetPane = document.querySelector(targetId);
            if(targetPane) {
                targetPane.classList.add('show', 'active');
            }
            
            if(this.id === 'kursus-tab') {
                document.getElementById('btnAddCategory').classList.add('d-none');
                document.getElementById('btnAddCourse').classList.remove('d-none');
            } else {
                document.getElementById('btnAddCategory').classList.remove('d-none');
                document.getElementById('btnAddCourse').classList.add('d-none');
            }
        });
    });


document.addEventListener('DOMContentLoaded', function() {
    categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
    categoryForm = document.getElementById('categoryForm');
    
    categoryForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(categoryForm);
        
        document.getElementById('btnSaveCat').disabled = true;
        document.getElementById('btnSaveCat').innerText = 'Saving...';
        
        try {
            const res = await fetch('/admin/program/category/save', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if(data.status === 'success') {
                categoryModal.hide();
                window.location.reload();
            } else {
                AgyToast.error('Error', data.message || 'Error saving category');
            }
        } catch(e) {
            AgyToast.error('Error', 'Network error occurred');
        }
        
        document.getElementById('btnSaveCat').disabled = false;
        document.getElementById('btnSaveCat').innerText = 'Save Category';
    });
});

function openCategoryModal() {
    categoryForm.reset();
    document.getElementById('cat_id').value = '';
    document.getElementById('categoryModalTitle').innerText = 'Add Category';
    categoryModal.show();
}

async function editCategory(id) {
    try {
        const res = await fetch('/admin/program/category/get/' + id);
        const data = await res.json();
        
        document.getElementById('cat_id').value = data.id;
        document.getElementById('cat_name').value = data.name;
        document.getElementById('cat_desc').value = data.description;
        document.getElementById('cat_parent').value = data.parent_id || '';
                try {
            let iconData = JSON.parse(data.icon);
            document.getElementById('cat_icon').value = iconData.icon || '';
        } catch(e) {
            document.getElementById('cat_icon').value = data.icon || '';
        }
        
        document.getElementById('categoryModalTitle').innerText = 'Edit Category';
        categoryModal.show();
    } catch(e) {
        AgyToast.error('Error', 'Failed to fetch category data');
    }
}

async function deleteCategory(id) {
    const confirmed = await AgyConfirm.show('Delete Category?', 'This action cannot be undone. Are you sure you want to proceed?', 'danger', 'Delete');
    if(!confirmed) return;
    try {
        const res = await fetch('/admin/program/category/delete/' + id);
        const data = await res.json();
        if(data.status === 'success') {
            AgyToast.success('Success', data.message || 'Category deleted');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            AgyToast.error('Error', data.message || 'Failed to delete category');
        }
    } catch(e) {
        AgyToast.error('Error', 'Failed to delete category');
    }
}
</script>


</div>

<?= $this->endSection() ?>






