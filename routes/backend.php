<?php


use Buki\AutoRoute\Facades\Route as AutoRoute;
use Illuminate\Support\Facades\Route;

AutoRoute::auto('', \App\Http\Controllers\Backend\HomeController::class, ['name' => 'home']);

AutoRoute::auto('contacts', \App\Http\Controllers\Backend\ContactController::class, [
    'name' => 'contacts',
    'middleware' => ['can:contacts:view'],
]);
AutoRoute::auto('newsletter', \App\Http\Controllers\Backend\NewsletterController::class, [
    'name' => 'newsletter',
    'middleware' => ['can:newsletter:view'],
]);
AutoRoute::auto('blogs', \App\Http\Controllers\Backend\BlogController::class, [
    'name' => 'blogs',
    'middleware' => ['can:blogs:view'],
]);
AutoRoute::auto('pages', \App\Http\Controllers\Backend\PageController::class, [
    'name' => 'pages',
    'middleware' => ['can:pages:view'],
]);
AutoRoute::auto('faqs', \App\Http\Controllers\Backend\FaqController::class, [
    'name' => 'pages.faqs',
    'middleware' => ['can:pages:view'],
]);
AutoRoute::auto('lessons', \App\Http\Controllers\Backend\LessonController::class, [
    'name' => 'lessons',
    'middleware' => ['can:lessons:view'],
]);
AutoRoute::auto('topics', \App\Http\Controllers\Backend\TopicController::class, [
    'name' => 'topics',
    'middleware' => ['can:topics:view'],
]);
AutoRoute::auto('questions', \App\Http\Controllers\Backend\QuestionController::class, [
    'name' => 'questions',
    'middleware' => ['can:questions:view'],
]);
AutoRoute::auto('exams', \App\Http\Controllers\Backend\ExamController::class, [
    'name' => 'exams',
    'middleware' => ['can:exams:view'],
]);
AutoRoute::auto('users', \App\Http\Controllers\Backend\UsersController::class, [
    'name' => 'users',
    'middleware' => ['can:users:view'],
]);
AutoRoute::auto('roles', \App\Http\Controllers\Backend\RolesController::class, [
    'name' => 'roles',
    'middleware' => ['can:roles:view'],
]);
AutoRoute::auto('settings', \App\Http\Controllers\Backend\SettingsController::class, [
    'name' => 'settings',
    'middleware' => ['can:settings:view'],
]);
AutoRoute::auto('languages', \App\Http\Controllers\Backend\LanguageController::class, [
    'name' => 'languages',
    'middleware' => ['can:languages:view'],
]);

Route::get('logout', [\App\Http\Controllers\Backend\Auth\LogoutController::class, 'index'])
    ->name('logout');

