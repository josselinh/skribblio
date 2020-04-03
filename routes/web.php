<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', 'WelcomeController@index')->name('home');

Route::namespace('Sentence')->prefix('sentence')->group(function () {
    Route::get('/export', 'SentenceController@export')->name('sentence.export');
});

Route::middleware(['auth'])->group(function () {
    Route::namespace('Group')->prefix('group')->group(function () {
        Route::get('/', 'GroupController@index')->name('group.index');
        Route::post('/add/do', 'GroupController@doAdd')->name('group.add.do');
    });

    Route::namespace('Sentence')->prefix('sentence')->group(function () {
        Route::get('/', 'SentenceController@index')->name('sentence.index');
        Route::post('/add/do', 'SentenceController@doAdd')->name('sentence.add.do');
        Route::get('/import', 'SentenceController@import')->name('sentence.import');
        Route::post('/import/do', 'SentenceController@doImport')->name('sentence.import.do');
    });
});
