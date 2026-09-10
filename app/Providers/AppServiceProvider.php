<?php

namespace App\Providers;

use App\Services\CakePointNotificationService;
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
                    'cakePointNotificationCount' => 0,
                    'cakePointNotifications' => collect(),
                ]);

                return;
            }

            $service = app(CakePointNotificationService::class);
            $user = auth()->user();

            $view->with([
                'cakePointNotificationCount' => $service->unreadCount($user),
                'cakePointNotifications' => $service->recent($user),
            ]);
        });
    }
}
