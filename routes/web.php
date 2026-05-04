<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Maršruti ir sadalīti pa failiem pēc atbildības:
// auth.php - pieslēgšanās/paroles atjaunošana,
// app.php - autorizēta lietotāja ikdienas funkcijas,
// admin.php - tikai administratoram,
// manager.php - pārvaldības darbības inventāram un remontiem.
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/manager.php';
require __DIR__.'/app.php';

Route::get('/', function () {
    // Sākuma URL ir tikai ieejas punkts.
    // Ja lietotājs jau ir pieslēdzies, vedam uz dashboard; citādi uz login.
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
})->name('home');
