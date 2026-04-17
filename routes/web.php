<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceAssetController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceTransferController;
use App\Http\Controllers\DeviceTypeController;
use App\Http\Controllers\LiveNotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RepairRequestController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserRequestCenterController;
use App\Http\Controllers\ViewModeController;
use App\Http\Controllers\WriteoffRequestController;
use Illuminate\Support\Facades\Route;

// Viesu sadaļa: autentifikācija un paroles atjaunošana.
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

// Tikai pilnam administratora skatam pieejamās darbības.
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/audit-log/find-entry', [AuditLogController::class, 'findEntry'])->name('audit-log.find-entry');
    Route::get('/users/find-by-name', [UserController::class, 'findByName'])->name('users.find-by-name');
    Route::get('/users/create', [UserController::class, 'redirectToCreateModal'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'redirectToEditModal'])->name('users.edit');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
});

// Inventāra pārvaldības sadaļa administratoram un IT darbiniekam.
Route::middleware(['auth', 'manager'])->group(function () {
    Route::get('/buildings/create', [BuildingController::class, 'redirectToCreateModal'])->name('buildings.create');
    Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::get('/buildings/{building}/edit', [BuildingController::class, 'redirectToEditModal'])->name('buildings.edit');
    Route::match(['put', 'patch'], '/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');
    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::get('/buildings/find-by-name', [BuildingController::class, 'findByName'])->name('buildings.find-by-name');
    Route::get('/rooms/create', [RoomController::class, 'redirectToCreateModal'])->name('rooms.create');
    Route::post('/rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/rooms/{room}/edit', [RoomController::class, 'redirectToEditModal'])->name('rooms.edit');
    Route::match(['put', 'patch'], '/rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/find-by-name', [RoomController::class, 'findByName'])->name('rooms.find-by-name');
    Route::get('/device-types/create', [DeviceTypeController::class, 'redirectToCreateModal'])->name('device-types.create');
    Route::post('/device-types', [DeviceTypeController::class, 'store'])->name('device-types.store');
    Route::get('/device-types/{deviceType}/edit', [DeviceTypeController::class, 'redirectToEditModal'])->name('device-types.edit');
    Route::match(['put', 'patch'], '/device-types/{deviceType}', [DeviceTypeController::class, 'update'])->name('device-types.update');
    Route::delete('/device-types/{deviceType}', [DeviceTypeController::class, 'destroy'])->name('device-types.destroy');
    Route::get('/device-types', [DeviceTypeController::class, 'index'])->name('device-types.index');
    Route::get('/devices/{device}/quick-update', [DeviceController::class, 'quickUpdateRedirect'])->name('devices.quick-update.redirect');
    Route::post('/devices/{device}/quick-update', [DeviceController::class, 'quickUpdate'])->name('devices.quick-update');
    Route::get('/devices/create', [DeviceController::class, 'redirectToCreateModal'])->name('devices.create');
    Route::post('/devices', [DeviceController::class, 'store'])->name('devices.store');
    Route::get('/devices/{device}/edit', [DeviceController::class, 'redirectToEditModal'])->name('devices.edit');
    Route::match(['put', 'patch'], '/devices/{device}', [DeviceController::class, 'update'])->name('devices.update');
    Route::delete('/devices/{device}', [DeviceController::class, 'destroy'])->name('devices.destroy');
    Route::post('/repairs/{repair}/transition', [RepairController::class, 'transition'])->name('repairs.transition');
    Route::post('/repairs/{repair}/completion', [RepairController::class, 'completion'])->name('repairs.completion');
    Route::get('/repairs/create', [RepairController::class, 'redirectToCreateModal'])->name('repairs.create');
    Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
    Route::get('/repairs/{repair}/edit', [RepairController::class, 'redirectToEditModal'])->name('repairs.edit');
    Route::match(['put', 'patch'], '/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');
    Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->name('repairs.destroy');
});

// Vispārēji autentificētā lietotāja maršruti.
Route::middleware('auth')->group(function () {
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/devices', [DashboardController::class, 'devices'])->name('dashboard.devices');
    Route::get('/live-notifications', [LiveNotificationController::class, 'index'])->name('live-notifications.index');
    Route::post('/notifications/mark-all-read', [LiveNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/view-mode', [ViewModeController::class, 'update'])->name('view-mode.update');
    Route::get('/my-requests', [UserRequestCenterController::class, 'index'])->name('my-requests.index');
    Route::get('/my-requests/create', [UserRequestCenterController::class, 'create'])->name('my-requests.create');
    Route::post('/my-requests', [UserRequestCenterController::class, 'store'])->name('my-requests.store');
    Route::get('/my-requests/{requestType}/{requestId}/edit', [UserRequestCenterController::class, 'edit'])->name('my-requests.edit');
    Route::patch('/my-requests/{requestType}/{requestId}', [UserRequestCenterController::class, 'update'])->name('my-requests.update');
    Route::delete('/my-requests/{requestType}/{requestId}', [UserRequestCenterController::class, 'destroy'])->name('my-requests.destroy');

    Route::get('/devices/find-by-code', [DeviceController::class, 'findByCode'])->name('devices.find-by-code');
    Route::get('/devices/table', [DeviceController::class, 'table'])->name('devices.table');
    Route::resource('devices', DeviceController::class)->only(['index', 'show']);
    Route::post('/devices/{device}/user-room', [DeviceController::class, 'updateUserRoom'])->name('devices.user-room.update');
    Route::get('/device-assets/remote-preview', [DeviceAssetController::class, 'remotePreview'])
        ->name('device-assets.remote-preview');
    Route::get('/device-assets/{path}', [DeviceAssetController::class, 'show'])
        ->where('path', '.*')
        ->name('device-assets.show');
    Route::resource('repairs', RepairController::class)->only(['index', 'show']);
    Route::get('/repairs/find-by-code', [RepairController::class, 'findByCode'])->name('repairs.find-by-code');
    Route::get('/repair-requests', [RepairRequestController::class, 'index'])->name('repair-requests.index');
    Route::get('/repair-requests/find-by-code', [RepairRequestController::class, 'findByCode'])->name('repair-requests.find-by-code');
    Route::get('/repair-requests/table', [RepairRequestController::class, 'table'])->name('repair-requests.table');
    Route::get('/repair-requests/create', [RepairRequestController::class, 'create'])->name('repair-requests.create');
    Route::post('/repair-requests', [RepairRequestController::class, 'store'])->name('repair-requests.store');
    Route::put('/repair-requests/{repairRequest}', [RepairRequestController::class, 'update'])->name('repair-requests.update');
    Route::post('/repair-requests/{repairRequest}/review', [RepairRequestController::class, 'review'])->name('repair-requests.review');
    Route::get('/writeoff-requests', [WriteoffRequestController::class, 'index'])->name('writeoff-requests.index');
    Route::get('/writeoff-requests/find-by-code', [WriteoffRequestController::class, 'findByCode'])->name('writeoff-requests.find-by-code');
    Route::get('/writeoff-requests/table', [WriteoffRequestController::class, 'table'])->name('writeoff-requests.table');
    Route::get('/writeoff-requests/create', [WriteoffRequestController::class, 'create'])->name('writeoff-requests.create');
    Route::post('/writeoff-requests', [WriteoffRequestController::class, 'store'])->name('writeoff-requests.store');
    Route::put('/writeoff-requests/{writeoffRequest}', [WriteoffRequestController::class, 'update'])->name('writeoff-requests.update');
    Route::post('/writeoff-requests/{writeoffRequest}/review', [WriteoffRequestController::class, 'review'])->name('writeoff-requests.review');
    Route::get('/device-transfers', [DeviceTransferController::class, 'index'])->name('device-transfers.index');
    Route::get('/device-transfers/find-by-code', [DeviceTransferController::class, 'findByCode'])->name('device-transfers.find-by-code');
    Route::get('/device-transfers/create', [DeviceTransferController::class, 'create'])->name('device-transfers.create');
    Route::post('/device-transfers', [DeviceTransferController::class, 'store'])->name('device-transfers.store');
    Route::put('/device-transfers/{deviceTransfer}', [DeviceTransferController::class, 'update'])->name('device-transfers.update');
    Route::post('/device-transfers/{deviceTransfer}/review', [DeviceTransferController::class, 'review'])->name('device-transfers.review');
});

// Saknes maršruts novirza uz atbilstošo sākuma lapu pēc pieslēgšanās stāvokļa.
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');
