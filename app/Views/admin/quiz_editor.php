<?= $this->extend('layouts/dashboard') ?>

<?= $this->section('content') ?>

<style>
    /* B2B Premium CRM Style - Editor View */
    h1, h2, h3, h4, h5, h6 { font-family: 'Plus Jakarta Sans', sans-serif; color: #0f172a; font-weight: 800; letter-spacing: -0.02em; }
    
    .agy-label { font-size: 0.85rem; color: #475569; font-weight: 700; margin-bottom: 8px; display: block; }
    
    /* Buttons */
    .agy-btn {
        display: inline-flex; align-items: center; justify-content: center;
        padding: 10px 20px; border-radius: 99px; font-weight: 700; font-size: 0.9rem;
        font-family: 'Plus Jakarta Sans', sans-serif; cursor: pointer; transition: all 0.2s ease;
        border: none; outline: none; text-decoration: none; gap: 8px;
    }
    .agy-btn-primary { background: #0f172a; color: white; }
    .agy-btn-primary:hover { background: #1e293b; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15); color: white; }
    .agy-btn-secondary { background: #f8fafc; color: #0f172a; border: 1px solid #e2e8f0; }
    .agy-btn-secondary:hover { background: #f1f5f9; border-color: #cbd5e1; color: #0f172a; }
    .agy-btn-back { color: #64748b; font-weight: 600; font-size: 0.95rem; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: color 0.2s; }
    .agy-btn-back:hover { color: #0f172a; }
    
    /* Custom Inputs */
    .agy-input, .agy-textarea {
        width: 100%; padding: 12px 16px; border: 1px solid #e2e8f0; border-radius: 12px;
        font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.95rem; color: #0f172a;
        background: #f8fafc; transition: all 0.2s; outline: none;
    }
    .agy-input:focus, .agy-textarea:focus { background: #fff; border-color: #94a3b8; box-shadow: 0 0 0 4px #f1f5f9; }
    
    .editor-container { background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 32px; margin-bottom: 24px; }
    
    /* Questions List */
    .question-card {
        border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px;
        background: #fff; transition: all 0.2s;
    }
    .question-card:hover { border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .q-type-badge {
        font-size: 0.7rem; font-weight: 800; padding: 4px 8px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px;
    }
    
    /* B2B Custom Modal */
    .agy-modal-backdrop {
        position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
        background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px);
        z-index: 1050; display: none; align-items: center; justify-content: center;
        opacity: 0; transition: opacity 0.3s;
    }
    .agy-modal {
        background: #fff; width: 100%; max-width: 650px; max-height: 90vh; overflow-y: auto;
        border-radius: 20px; box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        transform: translateY(20px); transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .agy-modal-backdrop.show { opacity: 1; display: flex; }
    .agy-modal-backdrop.show .agy-modal { transform: translateY(0); }
    .agy-modal-header { padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; position: sticky; top: 0; background: #fff; z-index: 10; }
    .agy-modal-title { font-weight: 800; font-size: 1.25rem; margin: 0; color: #0f172a; }
    .agy-modal-close { background: none; border: none; color: #94a3b8; font-size: 1.25rem; cursor: pointer; transition: color 0.2s; }
    .agy-modal-close:hover { color: #0f172a; }
    .agy-modal-body { padding: 24px; }
    .agy-modal-footer { padding: 20px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; position: sticky; bottom: 0; }
    
</style>

<div class="container-fluid p-0">
    
    <div class="d-flex align-items-center justify-content-between mb-4">
        <a href="/admin/program/course/details/<?= $course_id ?>" class="agy-btn-back">
            <i class="fas fa-arrow-left"></i> Back to Curriculum
        </a>
    </div>

    <div class="mb-4">
        <div class="d-flex align-items-center gap-3 mb-2">
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">
                QUIZ BUILDER
            </span>
            <span class="text-muted fw-bold" style="font-size: 0.85rem;"><i class="fas fa-folder-open text-primary me-2"></i> <?= esc($section_title) ?></span>
        </div>
        <h1 class="display-6 fw-bold text-dark mb-0">Quiz: <?= esc($lesson['title']) ?></h1>
    </div>

    <div class="row">
        <!-- Settings Form -->
        <div class="col-lg-4">
            <div class="editor-container" style="position: sticky; top: 20px;">
                <h4 class="mb-4">Quiz Settings</h4>
                <form id="quizSettingsForm">
                    <input type="hidden" id="quizId" value="<?= $quiz['id'] ?>">
                    
                    <div class="mb-3">
                        <label class="agy-label">Quiz Title</label>
                        <input type="text" id="quizTitle" class="agy-input" value="<?= esc($quiz['title']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="agy-label">Description / Instructions</label>
                        <textarea id="quizDesc" class="agy-textarea" rows="3"><?= esc($quiz['description']) ?></textarea>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="agy-label">Duration (Mins)</label>
                            <input type="number" id="quizDuration" class="agy-input" value="<?= esc($quiz['duration']) ?>" min="0">
                            <small class="text-muted">0 for unlimited</small>
                        </div>
                        <div class="col-6">
                            <label class="agy-label">Passing Score (%)</label>
                            <input type="number" id="quizPassing" class="agy-input" value="<?= esc($quiz['passing_score']) ?>" min="1" max="100">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="agy-label">Max Attempts</label>
                        <input type="number" id="quizMaxAttempts" class="agy-input" value="<?= esc($quiz['max_attempts']) ?>" min="1">
                    </div>
                    
                    <div class="mb-4 d-flex flex-column gap-2">
                        <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                            <input type="checkbox" id="quizShuffle" <?= $quiz['shuffle'] ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">Shuffle Options</span>
                        </label>
                        
                        <label class="d-flex align-items-center gap-2" style="cursor: pointer;">
                            <input type="checkbox" id="quizActive" <?= $quiz['is_active'] ? 'checked' : '' ?> style="width: 18px; height: 18px;">
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">Active / Published</span>
                        </label>
                    </div>
                    
                    <button type="button" class="agy-btn agy-btn-primary w-100" onclick="saveSettings()">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Questions Manager -->
        <div class="col-lg-8">
            <div class="editor-container">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <h4 class="mb-0">Questions (<?= count($questions) ?>)</h4>
                    <button class="agy-btn agy-btn-secondary" onclick="openQuestionModal()">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </div>
                
                <div id="questionsList">
                    <?php if (empty($questions)): ?>
                        <div class="text-center p-5 text-muted bg-light rounded-3 border">
                            <i class="fas fa-list-ul fa-3x mb-3 opacity-50"></i>
                            <h5 class="fw-bold text-dark">No questions added yet</h5>
                            <p>Start building your quiz by adding the first question.</p>
                            <button class="agy-btn agy-btn-primary mt-2" onclick="openQuestionModal()">Add Question</button>
                        </div>
                    <?php else: ?>
                        <?php foreach($questions as $index => $q): ?>
                            <div class="question-card">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="fw-bold text-muted">Q<?= $index + 1 ?>.</span>
                                        <?php if($q['type'] == 'multiple_choice'): ?>
                                            <span class="q-type-badge bg-info bg-opacity-10 text-info">Multiple Choice</span>
                                        <?php elseif($q['type'] == 'true_false'): ?>
                                            <span class="q-type-badge bg-warning bg-opacity-10 text-warning">True/False</span>
                                        <?php else: ?>
                                            <span class="q-type-badge bg-success bg-opacity-10 text-success">Essay</span>
                                        <?php endif; ?>
                                        <span class="q-type-badge bg-secondary bg-opacity-10 text-secondary"><?= $q['points'] ?> pts</span>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <!-- Delete Question -->
                                        <button class="btn btn-sm btn-light text-danger border" onclick="deleteQuestion('<?= $q['id'] ?>')"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                                <div class="fw-bold text-dark" style="font-size: 1.05rem;">
                                    <?= nl2br(esc($q['question'])) ?>
                                </div>
                                
                                <?php if($q['type'] == 'multiple_choice' || $q['type'] == 'true_false'): ?>
                                    <div class="mt-3 ps-4 border-start border-2 border-primary">
                                        <?php foreach($q['options'] as $opt): ?>
                                            <div class="d-flex gap-2 mb-1 <?= $opt['is_correct'] ? 'fw-bold text-success' : 'text-muted' ?>">
                                                <i class="fas <?= $opt['is_correct'] ? 'fa-check-circle' : 'fa-circle' ?> mt-1" style="font-size: 0.8rem;"></i>
                                                <div><?= esc($opt['option_text']) ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Question Modal -->
<div class="agy-modal-backdrop" id="questionModal">
    <div class="agy-modal">
        <div class="agy-modal-header">
            <h3 class="agy-modal-title" id="qModalTitle">Add Question</h3>
            <button class="agy-modal-close" onclick="closeQuestionModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="agy-modal-body">
            <input type="hidden" id="qId" value="">
            
            <div class="row mb-3">
                <div class="col-md-8">
                    <label class="agy-label">Question Type</label>
                    <select id="qType" class="agy-input" onchange="toggleOptionFields()">
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="true_false">True / False</option>
                        <option value="essay">Essay / Open Text</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="agy-label">Points</label>
                    <input type="number" id="qPoints" class="agy-input" value="1" min="1">
                </div>
            </div>
            
            <div class="mb-3">
                <label class="agy-label">Question Text</label>
                <textarea id="qText" class="agy-textarea" rows="4" placeholder="Type your question here..."></textarea>
            </div>
            
            <!-- Options Area -->
            <div id="optionsArea" class="bg-light p-3 rounded-3 border mb-3">
                <label class="agy-label d-flex justify-content-between">
                    <span>Options</span>
                    <span class="text-primary" style="cursor:pointer;" onclick="addOptionField()" id="addOptBtn"><i class="fas fa-plus"></i> Add Option</span>
                </label>
                <div id="optionsContainer" class="d-flex flex-column gap-2">
                    <!-- Option items injected via JS -->
                </div>
            </div>
            
            <div class="mb-3">
                <label class="agy-label">Explanation (Optional)</label>
                <textarea id="qExplain" class="agy-textarea" rows="2" placeholder="Shown to students after quiz"></textarea>
            </div>
            
        </div>
        <div class="agy-modal-footer">
            <button class="agy-btn agy-btn-secondary" onclick="closeQuestionModal()">Cancel</button>
            <button class="agy-btn agy-btn-primary" onclick="saveQuestion()">Save Question</button>
        </div>
    </div>
</div>

<script>
    const quizId = document.getElementById('quizId').value;
    
    async function saveSettings() {
        const formData = new FormData();
        formData.append('quiz_id', quizId);
        formData.append('title', document.getElementById('quizTitle').value);
        formData.append('description', document.getElementById('quizDesc').value);
        formData.append('duration', document.getElementById('quizDuration').value);
        formData.append('passing_score', document.getElementById('quizPassing').value);
        formData.append('max_attempts', document.getElementById('quizMaxAttempts').value);
        formData.append('shuffle', document.getElementById('quizShuffle').checked ? 1 : 0);
        formData.append('is_active', document.getElementById('quizActive').checked ? 1 : 0);
        
        try {
            const res = await fetch('/admin/program/quiz/settings/save', { method: 'POST', body: formData });
            const result = await res.json();
            if(result.status === 'success') {
                AgyToast.success(result.message);
            } else {
                AgyToast.error(result.message);
            }
        } catch(e) {
            AgyToast.error("Network Error");
        }
    }
    
    let optCount = 0;
    
    function openQuestionModal() {
        document.getElementById('qId').value = '';
        document.getElementById('qText').value = '';
        document.getElementById('qExplain').value = '';
        document.getElementById('qType').value = 'multiple_choice';
        document.getElementById('qPoints').value = '1';
        
        optCount = 0;
        document.getElementById('optionsContainer').innerHTML = '';
        // Add 4 default options
        addOptionField(); addOptionField(); addOptionField(); addOptionField();
        
        toggleOptionFields();
        document.getElementById('questionModal').classList.add('show');
    }
    
    function closeQuestionModal() {
        document.getElementById('questionModal').classList.remove('show');
    }
    
    function toggleOptionFields() {
        const type = document.getElementById('qType').value;
        const area = document.getElementById('optionsArea');
        const addBtn = document.getElementById('addOptBtn');
        
        if (type === 'essay') {
            area.style.display = 'none';
        } else if (type === 'true_false') {
            area.style.display = 'block';
            addBtn.style.display = 'none';
            // Force exactly 2 options: True and False
            optCount = 0;
            document.getElementById('optionsContainer').innerHTML = '';
            addOptionField('True', true);
            addOptionField('False', false);
        } else {
            area.style.display = 'block';
            addBtn.style.display = 'block';
            // If it was true/false previously, we might want to reset, but leaving it as is for now is fine.
        }
    }
    
    function addOptionField(val = '', isChecked = false) {
        const idx = optCount++;
        const html = `
        <div class="d-flex align-items-center gap-2 opt-row">
            <input type="radio" name="correct_opt" value="${idx}" ${isChecked ? 'checked' : (idx===0 ? 'checked' : '')} style="width: 20px; height: 20px; cursor: pointer;">
            <input type="text" class="agy-input opt-val" placeholder="Option text..." value="${val}" ${val ? '' : ''}>
            ${val ? '' : '<button class="btn btn-light text-danger border" onclick="this.closest(\'.opt-row\').remove()"><i class="fas fa-times"></i></button>'}
        </div>`;
        document.getElementById('optionsContainer').insertAdjacentHTML('beforeend', html);
    }
    
    async function saveQuestion() {
        const type = document.getElementById('qType').value;
        const question = document.getElementById('qText').value;
        
        if(!question.trim()) {
            AgyToast.error("Question text is required.");
            return;
        }
        
        const formData = new FormData();
        formData.append('quiz_id', quizId);
        formData.append('question_id', document.getElementById('qId').value);
        formData.append('type', type);
        formData.append('question', question);
        formData.append('points', document.getElementById('qPoints').value);
        formData.append('explanation', document.getElementById('qExplain').value);
        
        if (type !== 'essay') {
            const rows = document.querySelectorAll('.opt-row');
            let correctIdx = -1;
            let currentIdx = 0;
            let optionsValid = true;
            
            rows.forEach((row) => {
                const isCorrect = row.querySelector('input[type="radio"]').checked;
                const text = row.querySelector('.opt-val').value;
                if(!text.trim()) optionsValid = false;
                
                formData.append('options[]', text);
                if(isCorrect) correctIdx = currentIdx;
                currentIdx++;
            });
            
            if (!optionsValid) { AgyToast.error("All option fields must be filled"); return; }
            if (correctIdx === -1) { AgyToast.error("Please select a correct answer"); return; }
            formData.append('correct_index', correctIdx);
        }
        
        try {
            const res = await fetch('/admin/program/quiz/question/save', { method: 'POST', body: formData });
            const result = await res.json();
            if(result.status === 'success') {
                closeQuestionModal();
                AgyToast.success(result.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                AgyToast.error(result.message);
            }
        } catch (e) {
            AgyToast.error("Network Error");
        }
    }
    
    async function deleteQuestion(id) {
        const confirmed = await AgyConfirm.show('Delete Question?', 'This action cannot be undone.', 'danger', 'Yes, Delete');
        if(!confirmed) return;
        
        try {
            const res = await fetch('/admin/program/quiz/question/delete/' + id, {method: 'POST'});
            const result = await res.json();
            if(result.status === 'success') {
                AgyToast.success(result.message);
                setTimeout(() => window.location.reload(), 1000);
            } else {
                AgyToast.error(result.message);
            }
        } catch(e) {
            AgyToast.error('Network error');
        }
    }
</script>

<?= $this->endSection() ?>
