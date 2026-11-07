<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WelcomeController;
use App\Http\Controllers\Group\GroupController;
use App\Http\Controllers\Sentence\SentenceController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes();

Route::get('/', [WelcomeController::class, 'index'])->name('home');
Route::get('/release', [WelcomeController::class, 'release'])->name('release');

Route::prefix('sentence')->group(function () {
    Route::get('/export', [SentenceController::class, 'export'])->name('sentence.export');
});

Route::middleware(['auth'])->group(function () {
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
