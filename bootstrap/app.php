<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('sms:process-scheduled')->everyMinute();
        $schedule->command('sms:process-birthdays')->dailyAt('08:00');
        $schedule->command('sms:process-pickup-reminders')->dailyAt('08:15');
        $schedule->command('sms:process-low-stock-alerts')->dailyAt('09:00');
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'owner' => \App\Http\Middleware\EnsureOwner::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
