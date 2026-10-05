<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CategoryModel;

class ProgramController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        
        $courses = $db->table("courses c")
            ->select("c.id, c.title, c.thumbnail, c.level, c.status, c.price, c.created_at, cat.name as category_name, m.name as instructor_name")
            ->join("categories cat", "cat.id = c.category_id", "left")
            ->join("mentors m", "m.id = c.instructor_id", "left")
            ->where("c.deleted_at IS NULL")
            ->orderBy("c.created_at", "DESC")
            ->limit(12)
            ->get()
            ->getResultArray();

        $allCategories = $db->table("categories")
            ->select("id, parent_id, name, description, icon")
            ->where("is_active", 1)
            ->orderBy("parent_id", "ASC")
            ->orderBy("name", "ASC")
            ->get()
            ->getResultArray();
            
        $categories = [];
        $children = [];
        
        foreach ($allCategories as $cat) {
            if ($cat["parent_id"] === null) {
                $cat["children"] = [];
                $categories[$cat["id"]] = $cat;
            } else {
                $children[$cat["parent_id"]][] = $cat;
            }
        }
        
        foreach ($children as $parentId => $childList) {
            if (isset($categories[$parentId])) {
                $categories[$parentId]["children"] = $childList;
            }
        }
        
        $data = [
            "title" => "Program & Kelas",
            "courses" => $courses,
            "categories" => array_values($categories),
            "flat_categories" => $allCategories // passed for dropdowns
        ];

        return view("admin/program", $data);
    }
    
    public function loadMoreCourses()
    {
        $offset = (int) $this->request->getGet("offset");
        $limit = 12;
        
        $db = \Config\Database::connect();
        $courses = $db->table("courses c")
            ->select("c.id, c.title, c.thumbnail, c.level, c.status, c.price, c.created_at, cat.name as category_name, m.name as instructor_name")
            ->join("categories cat", "cat.id = c.category_id", "left")
            ->join("mentors m", "m.id = c.instructor_id", "left")
            ->where("c.deleted_at IS NULL")
            ->orderBy("c.created_at", "DESC")
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
            
        foreach ($courses as &$c) {
            $c["price_formatted"] = $c["price"] == 0 ? "Free" : "Rp " . number_format($c["price"], 0, ",", ".");
            $c["level"] = strtoupper($c["level"]);
            $c["instructor_name"] = htmlspecialchars($c["instructor_name"] ?? "");
            $c["category_name"] = htmlspecialchars($c["category_name"] ?? "");
            $c["title"] = htmlspecialchars($c["title"] ?? "");
            $c["thumbnail"] = $c["thumbnail"] ?: "https://images.unsplash.com/photo-1516321318423-f06f85e504b3?q=80&w=600&auto=format&fit=crop";
        }
            
        return $this->response->setJSON(["data" => $courses]);
    }
    
    public function saveCategory()
    {
        $catModel = new CategoryModel();
        
        $id = $this->request->getPost('id');
        $name = $this->request->getPost('name');
        
        $data = [
            'name' => $name,
            'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name))),
            'description' => $this->request->getPost('description'),
            'icon' => $this->request->getPost('icon') ?: 'fa-folder',
            'parent_id' => $this->request->getPost('parent_id') ?: null,
            'is_active' => 1
        ];
        
        if ($id) {
            $catModel->update($id, $data);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil diperbarui']);
        } else {
            $catModel->insert($data);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil ditambahkan']);
        }
    }
    
    public function deleteCategory($id)
    {
        $catModel = new CategoryModel();
        $catModel->update($id, ['is_active' => 0]); // Soft delete for categories by deactivating
        return $this->response->setJSON(['status' => 'success', 'message' => 'Kategori berhasil dihapus']);
    }
    
    public function getCategory($id)
    {
        $catModel = new CategoryModel();
        return $this->response->setJSON($catModel->find($id));
    }

    public function courseBuilder($id = null)
    {
        $db = \Config\Database::connect();
        
        $categories = $db->table('categories')
            ->select('id, name')
            ->where('is_active', 1)
            // ->where('parent_id IS NOT NULL') // Allowed top level categories for now
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
            
        $instructors = $db->table('mentors')
            ->select('id, name')
            ->where('deleted_at IS NULL')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
            
        $course = null;
        if ($id) {
            $courseModel = new \App\Models\CourseModel();
            $course = $courseModel->find($id); 
        }
        
        $data = [
            'title' => $id ? 'Edit Course' : 'Create New Course',
            'categories' => $categories,
            'instructors' => $instructors,
            'course' => $course
        ];
        
        return view('admin/course_builder', $data);
    }
    
    public function saveCourse()
    {
        $courseModel = new \App\Models\CourseModel();
        $id = $this->request->getPost('id');
        $title = $this->request->getPost('title');
        
        // Handle Thumbnail Upload
        $thumbnailPath = $this->request->getPost('old_thumbnail');
        $file = $this->request->getFile('thumbnail');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            $newName = $file->getRandomName();
            $file->move(FCPATH . 'uploads/courses', $newName);
            $thumbnailPath = 'uploads/courses/' . $newName;
        }
        
        $data = [
            'title' => $title,
            'slug' => strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))),
            'category_id' => $this->request->getPost('category_id'),
            'instructor_id' => $this->request->getPost('instructor_id'),
            'description' => $this->request->getPost('description'),
            'price' => $this->request->getPost('price'),
            'level' => $this->request->getPost('level'),
            'status' => $this->request->getPost('status'),
            'thumbnail' => $thumbnailPath
        ];
        
        if ($id) {
            $courseModel->update($id, $data);
        } else {
            $id = $courseModel->insert($data);
        }
        
        return redirect()->to('/admin/program')->with('success', 'Course saved successfully.');
    }

    public function courseDetails($id)
    {
        $db = \Config\Database::connect();
        
        $courseModel = new \App\Models\CourseModel();
        $course = $courseModel->find($id); 
        
        if (!$course) {
            return redirect()->to('/admin/program')->with('error', 'Course not found.');
        }
        
        // Fetch instructor name
        $instructor = $db->table('mentors')->select('name')->where('id', $course['instructor_id'])->get()->getRowArray();
        $course['instructor_name'] = $instructor ? $instructor['name'] : 'Unknown';
        
        // Fetch category name
        $category = $db->table('categories')->select('name')->where('id', $course['category_id'])->get()->getRowArray();
        $course['category_name'] = $category ? $category['name'] : 'Uncategorized';
        
        // Fetch sections and lessons (Curriculum)
        // Since we might not have these tables yet or they are empty, we handle it gracefully
        $sections = [];
        if ($db->tableExists('course_sections')) {
            $sections = $db->table('course_sections')->where('course_id', $id)->orderBy('order', 'ASC')->get()->getResultArray();
            if (!empty($sections) && $db->tableExists('lessons')) {
                foreach ($sections as &$sec) {
                    $sec['lessons'] = $db->table('lessons')->where('section_id', $sec['id'])->orderBy('order', 'ASC')->get()->getResultArray();
                }
            }
        }
        
        $data = [
            'title' => 'Course Details',
            'course' => $course,
            'sections' => $sections
        ];
        
        return view('admin/course_details', $data);
    }

    public function addSection()
    {
        $request = \Config\Services::request();
        $courseId = $request->getPost('course_id');
        $title = trim($request->getPost('title'));
        
        if (empty($title) || empty($courseId)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Course ID and Title are required']);
        }
        
        $sectionModel = new \App\Models\CourseSectionModel();
        
        // Find max order
        $db = \Config\Database::connect();
        $maxOrder = $db->table('course_sections')->where('course_id', $courseId)->selectMax('order')->get()->getRow()->order ?? 0;
        
        $data = [
            'course_id' => $courseId,
            'title' => $title,
            'order' => $maxOrder + 1
        ];
        
        try {
            $sectionModel->insert($data);
            $newId = $sectionModel->getInsertID();
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Section added successfully',
                'data' => [
                    'id' => $newId,
                    'title' => $title,
                    'order' => $maxOrder + 1
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save section: ' . $e->getMessage()]);
        }
    }

    public function addLesson()
    {
        $request = \Config\Services::request();
        $sectionId = $request->getPost('section_id');
        $title = trim($request->getPost('title'));
        $type = trim($request->getPost('type'));
        
        if (empty($title) || empty($sectionId) || empty($type)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'All fields are required']);
        }
        
        if (!in_array($type, ['video', 'document', 'text', 'quiz'])) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid lesson type']);
        }
        
        $lessonModel = new \App\Models\LessonModel();
        
        $db = \Config\Database::connect();
        $maxOrder = $db->table('lessons')->where('section_id', $sectionId)->selectMax('order')->get()->getRow()->order ?? 0;
        
        $data = [
            'section_id' => $sectionId,
            'title' => $title,
            'type' => $type,
            'content' => '', // Content will be added in Lesson Editor later
            'is_free' => 0,
            'order' => $maxOrder + 1
        ];
        
        try {
            $lessonModel->insert($data);
            $newId = $lessonModel->getInsertID();
            return $this->response->setJSON([
                'status' => 'success', 
                'message' => 'Lesson added successfully',
                'data' => [
                    'id' => $newId,
                    'title' => $title,
                    'type' => $type,
                    'order' => $maxOrder + 1
                ]
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Failed to save lesson: ' . $e->getMessage()]);
        }
    }

    public function lessonEditor($lessonId)
    {
        $db = \Config\Database::connect();
        
        $lessonModel = new \App\Models\LessonModel();
        $lesson = $lessonModel->find($lessonId);
        
        if (!$lesson) {
            return redirect()->to('/admin/program')->with('error', 'Lesson not found.');
        }
        
        // Fetch course and section details for breadcrumbs
        $section = $db->table('course_sections')->where('id', $lesson['section_id'])->get()->getRowArray();
        $courseId = $section ? $section['course_id'] : '';
        
        if ($lesson['type'] === 'quiz') {
            return $this->quizEditor($lesson, $courseId, $section ? $section['title'] : 'Unknown Section');
        }

        $data = [
            'title' => 'Edit Lesson',
            'lesson' => $lesson,
            'course_id' => $courseId,
            'section_title' => $section ? $section['title'] : 'Unknown Section'
        ];
        
        return view('admin/lesson_editor', $data);
    }
    
    private function quizEditor($lesson, $courseId, $sectionTitle)
    {
        $quizModel = new \App\Models\QuizModel();
        $quiz = $quizModel->where('lesson_id', $lesson['id'])->first();
        
        if (!$quiz) {
            // Create a default quiz for this lesson
            $quizData = [
                'course_id' => $courseId,
                'lesson_id' => $lesson['id'],
                'title' => $lesson['title'],
                'duration' => 30,
                'passing_score' => 70,
                'max_attempts' => 3,
                'shuffle' => 0,
                'is_active' => 1
            ];
            $quizModel->insert($quizData);
            // Since insert generates UUID, fetch it again
            $quiz = $quizModel->where('lesson_id', $lesson['id'])->first();
        }

        $questionModel = new \App\Models\QuestionModel();
        $optionModel = new \App\Models\QuestionOptionModel();
        
        $questions = $questionModel->where('quiz_id', $quiz['id'])->orderBy('order', 'ASC')->findAll();
        
        foreach($questions as &$q) {
            $q['options'] = $optionModel->where('question_id', $q['id'])->orderBy('order', 'ASC')->findAll();
        }

        $data = [
            'title' => 'Edit Quiz',
            'lesson' => $lesson,
            'course_id' => $courseId,
            'section_title' => $sectionTitle,
            'quiz' => $quiz,
            'questions' => $questions
        ];

        return view('admin/quiz_editor', $data);
    }
    
    public function saveLessonContent()
    {
        $request = \Config\Services::request();
        $lessonId = $request->getPost('id');
        $title = trim($request->getPost('title'));
        $content = trim($request->getPost('content'));
        $isFree = $request->getPost('is_free') ? 1 : 0;
        
        if (empty($lessonId) || empty($title)) {
            return redirect()->back()->with('error', 'Title is required');
        }
        
        $lessonModel = new \App\Models\LessonModel();
        $lesson = $lessonModel->find($lessonId);
        
        if (!$lesson) {
            return redirect()->back()->with('error', 'Lesson not found');
        }
        
        $updateData = [
            'title' => $title,
            'is_free' => $isFree
        ];
        
        // Handle file upload if provided
        $file = $request->getFile('file_content');
        if ($file && $file->isValid() && !$file->hasMoved()) {
            // Create uploads directory if not exists
            $uploadDir = FCPATH . 'uploads/lessons/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newName = $file->getRandomName();
            $file->move($uploadDir, $newName);
            
            // Delete old file if exists
            if (!empty($lesson['content']) && file_exists(FCPATH . $lesson['content']) && strpos($lesson['content'], 'uploads/lessons/') !== false) {
                unlink(FCPATH . $lesson['content']);
            }
            
            $updateData['content'] = 'uploads/lessons/' . $newName;
        } else if (!empty($content)) {
            // For text or external URL (like youtube)
            $updateData['content'] = $content;
        }
        
        try {
            $lessonModel->update($lessonId, $updateData);
            
            // Get course ID for redirection
            $db = \Config\Database::connect();
            $section = $db->table('course_sections')->where('id', $lesson['section_id'])->get()->getRowArray();
            $courseId = $section ? $section['course_id'] : '';
            
            return redirect()->to('/admin/program/course/details/' . $courseId)->with('success', 'Lesson updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to save lesson: ' . $e->getMessage());
        }
    }

    public function deleteSection($id)
    {
        $sectionModel = new \App\Models\CourseSectionModel();
        $section = $sectionModel->find($id);
        
        if ($section) {
            $sectionModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Section deleted']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Section not found']);
    }
    
    public function updateSection()
    {
        $request = \Config\Services::request();
        $id = $request->getPost('id');
        $title = trim($request->getPost('title'));
        
        if (empty($title) || empty($id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Title is required']);
        }
        
        $sectionModel = new \App\Models\CourseSectionModel();
        if ($sectionModel->find($id)) {
            $sectionModel->update($id, ['title' => $title]);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Section updated', 'title' => $title]);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Section not found']);
    }
    
    public function deleteLesson($id)
    {
        $lessonModel = new \App\Models\LessonModel();
        $lesson = $lessonModel->find($id);
        
        if ($lesson) {
            $lessonModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Lesson deleted']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Lesson not found']);
    }

    public function checkDb()
    {
        $db = \Config\Database::connect();
        $tables = $db->listTables();
        return $this->response->setJSON($tables);
    }
    
    // --- QUIZ endpoints ---
    public function saveQuizSettings()
    {
        $request = \Config\Services::request();
        $quizId = $request->getPost('quiz_id');
        if (!$quizId) return $this->response->setJSON(['status'=>'error', 'message'=>'Quiz ID required']);
        
        $quizModel = new \App\Models\QuizModel();
        $data = [
            'title' => $request->getPost('title'),
            'description' => $request->getPost('description'),
            'duration' => (int)$request->getPost('duration'),
            'passing_score' => (int)$request->getPost('passing_score'),
            'max_attempts' => (int)$request->getPost('max_attempts'),
            'shuffle' => $request->getPost('shuffle') ? 1 : 0,
            'is_active' => $request->getPost('is_active') ? 1 : 0,
        ];
        $quizModel->update($quizId, $data);
        return $this->response->setJSON(['status'=>'success', 'message'=>'Quiz settings saved!']);
    }
    
    public function saveQuestion()
    {
        $request = \Config\Services::request();
        $questionId = $request->getPost('question_id');
        $quizId = $request->getPost('quiz_id');
        $type = $request->getPost('type');
        $question = $request->getPost('question');
        $points = (int)$request->getPost('points');
        $explanation = $request->getPost('explanation');
        
        $questionModel = new \App\Models\QuestionModel();
        $optionModel = new \App\Models\QuestionOptionModel();
        $db = \Config\Database::connect();
        
        $qData = [
            'quiz_id' => $quizId,
            'type' => $type,
            'question' => $question,
            'points' => $points,
            'explanation' => $explanation
        ];
        
        $db->transStart();
        
        if ($questionId) {
            $questionModel->update($questionId, $qData);
            $optionModel->where('question_id', $questionId)->delete(); // Clear old options
        } else {
            $maxOrder = $db->table('questions')->where('quiz_id', $quizId)->selectMax('order')->get()->getRow()->order ?? 0;
            $qData['order'] = $maxOrder + 1;
            $questionModel->insert($qData);
            // Since insert uses generateUuid, we need to fetch it
            $questionId = $questionModel->getInsertID(); // getInsertID returns the string ID because of how the model is setup? Wait, let's fetch by max order or just generate it ourselves to be safe.
            if (!$questionId) {
                 $inserted = $db->table('questions')->where('quiz_id', $quizId)->orderBy('created_at', 'DESC')->limit(1)->get()->getRowArray();
                 $questionId = $inserted['id'];
            }
        }
        
        if ($type === 'multiple_choice' || $type === 'true_false') {
            $options = $request->getPost('options'); // Array of strings
            $correctIndex = (int)$request->getPost('correct_index'); // 0-based
            
            if ($options && is_array($options)) {
                foreach($options as $i => $optText) {
                    $optionModel->insert([
                        'question_id' => $questionId,
                        'option_text' => $optText,
                        'is_correct' => ($i === $correctIndex) ? 1 : 0,
                        'order' => $i + 1
                    ]);
                }
            }
        }
        
        $db->transComplete();
        
        if ($db->transStatus() === false) {
             return $this->response->setJSON(['status'=>'error', 'message'=>'Failed to save question']);
        }
        
        return $this->response->setJSON(['status'=>'success', 'message'=>'Question saved successfully']);
    }
    
    public function deleteQuestion($id)
    {
        $questionModel = new \App\Models\QuestionModel();
        if ($questionModel->find($id)) {
            $questionModel->delete($id);
            return $this->response->setJSON(['status' => 'success', 'message' => 'Question deleted']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Question not found']);
    }
}





