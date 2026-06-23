<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ManageController;

Route::get('/login',  [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'login']) ->middleware('throttle:5,1');//5 ครั้งต่อนาที
Route::post('/logout',[LoginController::class, 'logout'])->name('logout');

Route::middleware('auth.session')->group(function () {
    Route::get('/',          fn() => redirect('/dashboard'));
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/credentials/import', [DashboardController::class, 'importCredentials'])->middleware('role:staff')->name('credentials.import');
    Route::post('/stores/import', [DashboardController::class, 'importStores'])->middleware('role:admin')->name('stores.import');
    Route::post('/store', [DashboardController::class, 'createStore'])->middleware('role:admin');
    Route::get('/dashboard/export',[DashboardController::class, 'export'])->middleware('role:admin')->name('dashboard.export');
    Route::get('/credentials/export', [DashboardController::class, 'exportCredentials'])->middleware('role:staff')->name('credentials.export');
    Route::put('/credentials/{storeId}', [DashboardController::class, 'updateCredential'])->middleware('role:staff,admin')->name('credentials.update');
    Route::put('/store/{storeId}', [DashboardController::class, 'updateStore'])->middleware('role:admin')->name('store.update');
    Route::delete('/store/{storeId}', [DashboardController::class, 'destroyStore'])->middleware('role:admin')->name('store.destroy');
    Route::get('/store/{storeId}/credentials', [DashboardController::class, 'getCredentials']);
    Route::get('/store/{storeId}/info',        [DashboardController::class, 'getStore']);
});

Route::middleware(['auth.session', 'role:admin'])->prefix('manage')->group(function(){
    Route::get('/', [ManageController::class, 'index'])->name('manage.index');
    Route::post('/bu/rename', [ManageController::class, 'renameBU']);
    Route::post('/bu/add', [ManageController::class, 'addBU']);
    Route::post('/bu/delete', [ManageController::class, 'deleteBU']);
    Route::post('/rm/rename', [ManageController::class, 'renameRM']);
    Route::post('/rm/add', [ManageController::class, 'addRM']);
    Route::post('/rm/delete', [ManageController::class, 'deleteRM']);
    Route::post('/am', [ManageController::class, 'createAM']);
    Route::put('/am/{id}', [ManageController::class, 'updateAM']);
    Route::delete('/am/{id}', [ManageController::class, 'deleteAM']);
    Route::post('/system', [ManageController::class, 'storeSystem']);
    Route::put('/system/{id}', [ManageController::class, 'updateSystem']);
    Route::delete('/system/{id}', [ManageController::class, 'destroySystem']);
});