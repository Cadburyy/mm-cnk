<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WeightController;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::middleware(['auth'])->group(function () {
    Route::resource('items', ItemController::class);
    Route::post('/items/bulk-destroy', [ItemController::class, 'bulkDestroy'])->name('items.bulkDestroy');
});

Route::middleware(['auth', 'role:AdminIT|Admin'])->group(function () {
    Route::resource('weights', WeightController::class);
    Route::post('/weights/bulk-destroy', [WeightController::class, 'bulkDestroy'])->name('weights.bulkDestroy');
    
    Route::resource('budget', BudgetController::class);
    Route::post('/budget/bulk-destroy', [BudgetController::class, 'bulkDestroy'])->name('budget.bulkDestroy');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/appearance', [SettingsController::class, 'editAppearance'])->name('settings.appearance');
    Route::put('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');

    Route::resource('users', UserController::class);
});

Route::middleware(['auth', 'role:AdminIT'])->group(function () {
    Route::resource('roles', RoleController::class);
});