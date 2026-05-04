<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Admin grupā ir lietotāju pārvaldība, audit logs un reģistrācija.
// `auth` pārbauda pieslēgšanos, `admin` pārbauda lomu.
Route::middleware(['auth', 'admin'])->group(function () {
    // Register šeit nav publiska pašreģistrācija, bet administratora izveidots jauns lietotājs.
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    // Manuālā audit ieraksta meklēšana tiek lietota async tabulā, lai atrastu un izceltu konkrētu rindu.
    Route::get('/audit-log/find-entry', [AuditLogController::class, 'findEntry'])->name('audit-log.find-entry');

    // Lietotāju sarakstam ir atsevišķs find endpoint, lai manuālā meklēšana varētu strādāt pāri lapām.
    Route::get('/users/find-by-name', [UserController::class, 'findByName'])->name('users.find-by-name');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
});
