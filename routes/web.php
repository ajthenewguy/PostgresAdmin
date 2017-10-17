<?php

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

Route::get('/', 'FrontController@index');
Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::get('/admin', 'AdminController@index')->name('home');
    Route::post('/switch-database', 'AdminController@switchDatabase');
    Route::post('/select', 'QueryController@select');
    Route::post('/insert', 'QueryController@insert');
    Route::post('/update', 'QueryController@update');
    Route::post('/delete', 'QueryController@delete');
    Route::post('/execute', 'QueryController@execute');
});
