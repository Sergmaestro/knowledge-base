<?php

use App\Http\Controllers\BookmarkController;
use App\Http\Controllers\BookmarksPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/topic/{slug}', [TopicController::class, 'show'])->name('topic.show');
Route::get('/question/{slug}', [QuestionController::class, 'show'])->name('question.show');
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/api/search', [SearchController::class, 'apiIndex'])->name('search.api');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/bookmarks', [BookmarksPageController::class, 'index'])->name('bookmarks');
    Route::post('/progress/toggle', [ProgressController::class, 'toggle'])->name('progress.toggle');
    Route::get('/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::post('/bookmark/toggle', [BookmarkController::class, 'toggle'])->name('bookmark.toggle');
});

require __DIR__.'/auth.php';
