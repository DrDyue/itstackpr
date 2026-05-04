<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use Illuminate\Support\Facades\Route;

// `guest` middleware neļauj jau pieslēgtam lietotājam atvērt login/paroles atjaunošanas formu.
Route::middleware('guest')->group(function () {
    // Login forma un login POST ir vienā kontrolierī, lai skats un autentifikācijas mēģinājums būtu vienā plūsmā.
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // Šajā projektā paroles atjaunošanas pieprasījums tiek reģistrēts sistēmā,
    // lai administrators varētu reaģēt uz lietotāja pieprasījumu.
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');
});
