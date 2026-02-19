<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\DeviceHistoryController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DeviceSetController;
use App\Http\Controllers\DeviceSetItemController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
    
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('employees', EmployeeController::class)->except(['show']);
    Route::resource('buildings', BuildingController::class)->except(['show']);
    Route::resource('rooms', RoomController::class)->except(['show']);
    Route::resource('device-types', DeviceTypeController::class)->except(['show']);
    Route::resource('devices', DeviceController::class)->except(['show']);
    Route::resource('repairs', RepairController::class)->except(['show']);
    Route::get('/device-history', [DeviceHistoryController::class, 'index'])->name('device-history.index');
    Route::get('/devices/{device}/history', [DeviceHistoryController::class, 'device'])->name('devices.history');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::resource('device-sets', DeviceSetController::class)->except(['show']);
    Route::post('/device-sets/{deviceSet}/items', [DeviceSetController::class, 'addItem'])
        ->name('device-sets.items.add');
    Route::delete('/device-sets/{deviceSet}/items/{item}', [DeviceSetController::class, 'deleteItem'])
        ->name('device-sets.items.delete');
    Route::resource('device-set-items', DeviceSetItemController::class)->except(['show']);
    Route::resource('users', UserController::class)->except(['show']);
});

Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');
