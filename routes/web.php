<?php

use App\Http\Controllers\AnswerNoteController;
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
Route::get('/search', [SearchController::class, 'search'])->name('search');

Route::middleware('auth')->group(function () {
    Route::group(['prefix' => 'profile', 'as' => 'profile.'], function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    Route::group(['prefix' => 'progress', 'as' => 'progress.'], function () {
        Route::get('/', [ProgressController::class, 'index'])->name('index');
        Route::post('/toggle', [ProgressController::class, 'toggle'])->name('toggle');
    });

    Route::group(['prefix' => 'bookmarks', 'as' => 'bookmarks.'], function () {
        Route::get('/', [BookmarkController::class, 'index'])->name('index');
        Route::post('/toggle', [BookmarkController::class, 'toggle'])->name('toggle');
    });

    Route::group(['prefix' => 'notes', 'as' => 'notes.'], function () {
        Route::post('/', [AnswerNoteController::class, 'store'])->name('store');
        Route::patch('/{note}', [AnswerNoteController::class, 'update'])->name('update');
        Route::delete('/{note}', [AnswerNoteController::class, 'destroy'])->name('destroy');
    });
});

require __DIR__.'/auth.php';
