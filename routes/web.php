<?php

use App\Http\Controllers\Group\GroupController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Sentence\SentenceController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WelcomeController::class, 'index'])->name('home');

Route::prefix('sentence')->group(function () {
    Route::get('/export', [SentenceController::class, 'export'])->name('sentence.export');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('sentence.index');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('group')->group(function () {
        Route::get('/', [GroupController::class, 'index'])->name('group.index');
        Route::get('/add', [GroupController::class, 'add'])->name('group.add');
        Route::post('/add/do', [GroupController::class, 'doAdd'])->name('group.add.do');
    });

    Route::prefix('sentence')->group(function () {
        Route::get('/', [SentenceController::class, 'index'])->name('sentence.index');
        Route::get('/add', [SentenceController::class, 'add'])->name('sentence.add');
        Route::post('/add/do', [SentenceController::class, 'doAdd'])->name('sentence.add.do');
        Route::get('/import', [SentenceController::class, 'import'])->name('sentence.import');
        Route::post('/import/do', [SentenceController::class, 'doImport'])->name('sentence.import.do');
        Route::post('/vote/do', [SentenceController::class, 'doVote'])->name('sentence.vote.do');
    });
});

require __DIR__.'/auth.php';
