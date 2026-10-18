<?php

namespace App\Providers;

use App\Services\AppNotificationService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFour();

        View::composer(['layouts.partials._header', 'layouts.partials._sidebar'], function ($view) {
            if (! auth()->check()) {
                $view->with([
                    'appNotificationCount' => 0,
                    'appNotifications' => [],
                ]);

                return;
            }

            $notifications = app(AppNotificationService::class)->alerts(auth()->user());

            $view->with([
                'appNotificationCount' => count($notifications),
                'appNotifications' => $notifications,
            ]);
        });
    }
}
