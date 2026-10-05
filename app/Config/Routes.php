<?php
use CodeIgniter\Router\RouteCollection;
/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index'); 

// =====================================================
// AUTH ROUTES
// =====================================================
$routes->group('auth', static function ($routes) {
    $routes->get('login', 'Auth\Login::index');
    $routes->post('login', 'Auth\Login::process');
    $routes->get('logout', 'Auth\Login::logout');
});

// Alias routes
$routes->get('masuk', 'Auth\Login::index');
$routes->post('masuk', 'Auth\Login::process');
$routes->get('keluar', 'Auth\Login::logout');

$routes->get('daftar', 'Auth\Register::index');
$routes->post('daftar', 'Auth\Register::process');

// =====================================================
$routes->get('transaksi', 'Admin\TransaksiController::index');
$routes->post('transaksi/approve', 'Admin\TransaksiController::approve');
$routes->post('transaksi/get-data', 'Admin\TransaksiController::getData');
$routes->get('transaksi/detail/(:segment)', 'Admin\TransaksiController::detail/$1');

// ADMIN ROUTES
// =====================================================
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    
    // Nanti akan diisi route khusus admin
    $routes->get('program', 'ProgramController::index');
    $routes->get('program/load-more', 'ProgramController::loadMoreCourses');
    
    // Category CRUD
    $routes->post('program/category/save', 'ProgramController::saveCategory');
    $routes->get('program/category/delete/(:segment)', 'ProgramController::deleteCategory/$1');
    
    // Course CRUD
    $routes->get('program/course/builder', 'ProgramController::courseBuilder');
    $routes->get('program/course/builder/(:segment)', 'ProgramController::courseBuilder/$1');
    $routes->get('program/course/details/(:segment)', 'ProgramController::courseDetails/$1');
        
        
        
        
        
        
    $routes->post('program/course/save', 'ProgramController::saveCourse');
    $routes->post('program/section/add', 'ProgramController::addSection');
    $routes->post('program/lesson/add', 'ProgramController::addLesson');
    $routes->get('program/lesson/editor/(:segment)', 'ProgramController::lessonEditor/$1');
    $routes->post('program/lesson/save', 'ProgramController::saveLessonContent');
    $routes->post('program/section/delete/(:segment)', 'ProgramController::deleteSection/$1');
    $routes->post('program/section/update', 'ProgramController::updateSection');
    $routes->post('program/lesson/delete/(:segment)', 'ProgramController::deleteLesson/$1');
    $routes->get('program/category/get/(:segment)', 'ProgramController::getCategory/$1');
    
    // Quiz Builder Routes
    $routes->post('program/quiz/settings/save', 'ProgramController::saveQuizSettings');
    $routes->post('program/quiz/question/save', 'ProgramController::saveQuestion');
    $routes->post('program/quiz/question/delete/(:segment)', 'ProgramController::deleteQuestion/$1');


});



$routes->post('admin/api/toggle-suspend', 'Admin\Dashboard::toggleSuspend');
$routes->post('admin/api/toggle-mute', 'Admin\Dashboard::toggleMute');
$routes->get('admin/api/check-new-orders', 'Admin\Dashboard::checkNewOrders');
$routes->get('admin/api/simulate-order', 'Admin\Dashboard::simulateOrder');
$routes->get('api/platform-status', 'Admin\Dashboard::platformStatus');


// Laporan (Root Level)
$routes->get('report', 'Admin\LaporanController::index');
$routes->get('report/export', 'Admin\LaporanController::export');
