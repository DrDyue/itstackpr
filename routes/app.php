<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceAssetController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceTransferController;
use App\Http\Controllers\LiveNotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\RepairRequestController;
use App\Http\Controllers\UserRequestCenterController;
use App\Http\Controllers\ViewModeController;
use App\Http\Controllers\WriteoffRequestController;
use Illuminate\Support\Facades\Route;

// Visi šie maršruti prasa autentificētu lietotāju.
// Lomu atšķirības tālāk tiek risinātas kontrolieros, politikās vai atsevišķajos admin/manager route failos.
Route::middleware('auth')->group(function () {
    // Konta pamatdarbības ir pieejamas jebkuram pieslēgtam lietotājam.
    Route::put('password', [PasswordController::class, 'update'])
        ->name('password.update');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/settings', [ProfileController::class, 'updateSettings'])->name('profile.settings.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/devices', [DashboardController::class, 'devices'])->name('dashboard.devices');
    // Live paziņojumi tiek vilkti ar polling no frontend JS.
    // Atsevišķs mark-all-read endpoint saglabā navigācijas badge izlasīšanas robežu.
    Route::get('/live-notifications', [LiveNotificationController::class, 'index'])->name('live-notifications.index');
    Route::post('/notifications/mark-all-read', [LiveNotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    Route::post('/view-mode', [ViewModeController::class, 'update'])->name('view-mode.update');

    Route::get('/my-requests', [UserRequestCenterController::class, 'index'])->name('my-requests.index');
    // Vienotais "my-requests" endpoint ļauj lietotājam labot/atcelt savus pieteikumus pēc tipa,
    // neatkarīgi no tā, vai tas ir remonts, norakstīšana vai nodošana.
    Route::post('/my-requests', [UserRequestCenterController::class, 'store'])->name('my-requests.store');
    Route::patch('/my-requests/{requestType}/{requestId}', [UserRequestCenterController::class, 'update'])->name('my-requests.update');
    Route::delete('/my-requests/{requestType}/{requestId}', [UserRequestCenterController::class, 'destroy'])->name('my-requests.destroy');

    Route::get('/devices/find-by-code', [DeviceController::class, 'findByCode'])->name('devices.find-by-code');
    // `/table` endpointi atgriež tikai tabulas fragmentu async filtrēšanai.
    // Pilnā `index` lapa paliek izmantojama arī bez JavaScript.
    Route::get('/devices/table', [DeviceController::class, 'table'])->name('devices.table');
    Route::resource('devices', DeviceController::class)->only(['index', 'show']);
    Route::post('/devices/{device}/user-room', [DeviceController::class, 'updateUserRoom'])->name('devices.user-room.update');

    Route::get('/device-assets/remote-preview', [DeviceAssetController::class, 'remotePreview'])
        ->name('device-assets.remote-preview');
    // Attēla ceļš var saturēt apakšmapes, tāpēc regex atļauj pilnu path atlikumu.
    Route::get('/device-assets/{path}', [DeviceAssetController::class, 'show'])
        ->where('path', '.*')
        ->name('device-assets.show');

    Route::resource('repairs', RepairController::class)->only(['index', 'show']);
    Route::get('/repairs/find-by-code', [RepairController::class, 'findByCode'])->name('repairs.find-by-code');

    Route::get('/repair-requests', [RepairRequestController::class, 'index'])->name('repair-requests.index');
    Route::get('/repair-requests/find-by-code', [RepairRequestController::class, 'findByCode'])->name('repair-requests.find-by-code');
    Route::get('/repair-requests/table', [RepairRequestController::class, 'table'])->name('repair-requests.table');
    Route::post('/repair-requests', [RepairRequestController::class, 'store'])->name('repair-requests.store');
    Route::put('/repair-requests/{repairRequest}', [RepairRequestController::class, 'update'])->name('repair-requests.update');
    // Review darbība nav parasts update, jo tā maina pieteikuma lēmumu un var radīt remonta ierakstu.
    Route::post('/repair-requests/{repairRequest}/review', [RepairRequestController::class, 'review'])->name('repair-requests.review');

    Route::get('/writeoff-requests', [WriteoffRequestController::class, 'index'])->name('writeoff-requests.index');
    Route::get('/writeoff-requests/find-by-code', [WriteoffRequestController::class, 'findByCode'])->name('writeoff-requests.find-by-code');
    Route::get('/writeoff-requests/table', [WriteoffRequestController::class, 'table'])->name('writeoff-requests.table');
    Route::post('/writeoff-requests', [WriteoffRequestController::class, 'store'])->name('writeoff-requests.store');
    Route::put('/writeoff-requests/{writeoffRequest}', [WriteoffRequestController::class, 'update'])->name('writeoff-requests.update');
    // Norakstīšanas review var mainīt arī ierīces statusu, tāpēc tas ir atdalīts no teksta labošanas update.
    Route::post('/writeoff-requests/{writeoffRequest}/review', [WriteoffRequestController::class, 'review'])->name('writeoff-requests.review');

    Route::get('/device-transfers', [DeviceTransferController::class, 'index'])->name('device-transfers.index');
    Route::get('/device-transfers/find-by-code', [DeviceTransferController::class, 'findByCode'])->name('device-transfers.find-by-code');
    Route::post('/device-transfers', [DeviceTransferController::class, 'store'])->name('device-transfers.store');
    Route::put('/device-transfers/{deviceTransfer}', [DeviceTransferController::class, 'update'])->name('device-transfers.update');
    // Nodošanas review ir saņēmēja lēmums; apstiprināšanas gadījumā backend maina ierīces atbildīgo.
    Route::post('/device-transfers/{deviceTransfer}/review', [DeviceTransferController::class, 'review'])->name('device-transfers.review');
    Route::get('/device-transfers/{deviceTransfer}/act', [DeviceTransferController::class, 'printAct'])->name('device-transfers.act');
});
