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

//Route::get('/', 'FrontController@index');
Route::get('/token', 'FrontController@token');
Auth::routes();

Route::group(['middleware' => ['auth']], function () {
    Route::get('/admin', 'AdminController@index')->name('home');
    Route::post('/switch-database', 'AdminController@switchDatabase');
    Route::post('/tables', 'QueryController@tables');
    Route::post('/select', 'QueryController@select');
    Route::post('/insert', 'QueryController@insert');
    Route::post('/update', 'QueryController@update');
    Route::post('/delete', 'QueryController@delete');
    Route::post('/execute', 'QueryController@execute');
    Route::post('/schema', 'QueryController@schema');
    Route::get('/settings', 'SettingsController@get');
    Route::post('/settings', 'SettingsController@set');
    Route::get('/session', 'SessionController@get');
    Route::post('/session', 'SessionController@set');
});


//Route::get('/', function () {
//    return redirect('/'.($request->session()->get('selectedDatabase') ?: env('DB_CONNECTION')));
//});
Route::get('/{database?}', 'FrontController@index');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
