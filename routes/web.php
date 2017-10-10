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
    Route::post('/query', 'QueryController@execute');
//    Route::get('change-password', function () {
//        return view('auth.change-password', ['title' => 'Change Password']);
//    });
//    Route::post('change-password', 'Auth\UpdatePasswordController@update');
});
