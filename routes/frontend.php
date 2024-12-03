<?php

use App\Mail\RecoverPasswordMail;
use Buki\AutoRoute\Facades\Route as AutoRoute;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

use App\Models\User;
use App\Models\Blog;
use App\Models\Exam;
use App\Models\Lesson;
use App\Models\Topic;
use App\Models\Newsletter;
use App\Models\Page;

Route::get('/', [\App\Http\Controllers\Frontend\HomeController::class,'index'])
    ->name('home');
Route::get('search', [\App\Http\Controllers\Frontend\HomeController::class,'search'])
    ->name('search');
Route::get('page/{page:slug}', [\App\Http\Controllers\Frontend\HomeController::class,'page'])
    ->name('page');
Route::get('sitemap.xml', [\App\Http\Controllers\Frontend\HomeController::class,'sitemap'])
    ->name('sitemap');

Route::get('tests/{topic:code?}', [\App\Http\Controllers\Frontend\TestController::class,'index'])
    ->name('tests');
Route::get('test/{exam:code}', [\App\Http\Controllers\Frontend\TestController::class,'detail'])
    ->name('test.detail');
Route::get('test/{exam:code}/start', [\App\Http\Controllers\Frontend\TestController::class,'start'])
    ->name('test.start');
Route::get('test/{exam:code}/solutions', [\App\Http\Controllers\Frontend\TestController::class,'solutions'])
    ->name('test.solutions');

Route::get('lessons', [\App\Http\Controllers\Frontend\LessonController::class,'index'])
    ->name('lessons');
Route::get('lesson/{lesson:code}', [\App\Http\Controllers\Frontend\LessonController::class,'topics'])
    ->name('lesson');

Route::get('blog', [\App\Http\Controllers\Frontend\BlogController::class,'index'])
    ->name('blog');
Route::get('blog/{blog:slug}', [\App\Http\Controllers\Frontend\BlogController::class,'detail'])
    ->name('blog.detail');
Route::get('faqs', [\App\Http\Controllers\Frontend\FaqController::class,'index'])
    ->name('faqs');

Route::get('contact', [\App\Http\Controllers\Frontend\ContactController::class,'index'])
    ->name('contact');
Route::get('unsubscribe/{newsletter:token}', [\App\Http\Controllers\Frontend\AccountController::class,'unsubscribe'])->name('unsubscribe');

// frontend account
Route::middleware(['auth'])
    ->controller(\App\Http\Controllers\Frontend\AccountController::class)
    ->prefix('account')
    ->group(function () {
        Route::get('/profile', 'index')->name('profile');
        Route::get('/favorite', 'favorite')->name('favorite');
        Route::get('/solved', 'solved')->name('solved');
        Route::get('/logout', 'logout')->name('logout');
    });

// frontend login
Route::middleware(['guest'])
    ->controller(\App\Http\Controllers\Frontend\AuthController::class)
    ->prefix('auth')
    ->group(function () {
        Route::get('register', 'register')->name('register');
        Route::get('recover-password', 'recoverPassword')->name('recover.password');
        Route::get('create-password/{token}', 'createPassword')->name('create.password');
        Route::get('verify/{user:email_verified_token}', 'verify')->name('verify');
    });
