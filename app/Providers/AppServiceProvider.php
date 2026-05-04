<?php

namespace App\Providers;

use App\Support\NavigationViewData;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

/**
 * Aplikācijas servisu sniedzējs.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Reģistrē aplikācijas servisus.
     */
    public function register(): void
    {
        //
    }

    /**
     * Inicializē aplikācijas servisus pēc ielādes.
     */
    public function boot(): void
    {
        // Navigācijas dati tiek pievienoti ar View Composer, lai katrs skats,
        // kurā iekļauta `layouts.navigation`, automātiski saņemtu badge skaitļus un linku struktūru.
        View::composer('layouts.navigation', function ($view): void {
            $view->with(app(NavigationViewData::class)->data());
        });
    }
}
