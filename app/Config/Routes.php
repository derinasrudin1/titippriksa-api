<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// AUTHENTICATION ROUTES (Public - Tanpa Token)
$routes->group('api', function ($routes) {
    $routes->post('register', 'API\AuthController::register');
    $routes->post('login', 'API\AuthController::login');
});

// GENERAL PRIVATE ROUTES (Membutuhkan Token JWT valid)
// Akses: Siswa, Guru, Admin
$routes->group('api', ['filter' => 'jwt'], function ($routes) {
    $routes->get('dashboard/stats', 'API\DashboardController::studentStats');
    // --- Data Ujian & Soal ---
    $routes->get('exams', 'API\ExamController::index');
    $routes->get('exams/(:num)/questions', 'API\QuestionController::getByExam/$1');

    // --- Pengerjaan Ujian (Siswa) ---
    $routes->post('attempt/start', 'API\AttemptController::startExam');
    $routes->post('attempt/answer', 'API\AttemptController::saveAnswer');
    $routes->post('attempt/submit/(:num)', 'API\AttemptController::submitExam/$1');
});

// ROLE-BASED ROUTES (Khusus Guru & Admin)
// Membutuhkan Token JWT dengan role 'teacher' atau 'admin'
$routes->group('api', ['filter' => 'jwt:teacher,admin'], function ($routes) {

    // --- Manajemen Master Data ---
    $routes->post('exams', 'API\ExamController::create');
    $routes->post('questions', 'API\QuestionController::create');
});
