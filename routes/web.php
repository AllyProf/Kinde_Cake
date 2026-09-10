<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BusinessTrendsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayCloseController;
use App\Http\Controllers\DayReportController;
use App\Http\Controllers\CakePointController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientReceivingController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\PackageUnitController;
use App\Http\Controllers\PaymentProviderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StockController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/', fn () => redirect()->route('dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('permission:customers.view')->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    });

    Route::middleware('permission:sms.view')->group(function () {
        Route::get('/sms', [SmsController::class, 'index'])->name('sms.index');
    });

    Route::middleware('permission:sms.send')->group(function () {
        Route::post('/sms', [SmsController::class, 'store'])->name('sms.store');
        Route::delete('/sms/{sms}', [SmsController::class, 'destroy'])->name('sms.destroy');
    });

    Route::middleware('permission:customers.manage')->group(function () {
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    });

    Route::middleware('permission:orders.create')->group(function () {
        Route::get('/sales/create', [SaleController::class, 'create'])->name('sales.create');
        Route::post('/sales', [SaleController::class, 'store'])->name('sales.store');
        Route::post('/sales/{sale}/pay', [SaleController::class, 'pay'])->name('sales.pay');
    });

    Route::middleware('permission:orders.create|orders.manage')->group(function () {
        Route::post('/day-closes/staff', [DayCloseController::class, 'storeStaff'])->name('day-closes.staff.store');
        Route::post('/day-closes/expenses', [DayCloseController::class, 'storeExpense'])->name('day-closes.expenses.store');
        Route::delete('/day-closes/expenses/{expense}', [DayCloseController::class, 'destroyExpense'])->name('day-closes.expenses.destroy');
    });

    Route::middleware('owner')->group(function () {
        Route::post('/day-closes', [DayCloseController::class, 'store'])->name('day-closes.store');
        Route::get('/day-reports', [DayReportController::class, 'index'])->name('day-reports.index');
        Route::get('/business-trends', [BusinessTrendsController::class, 'index'])->name('business-trends.index');
    });

    Route::middleware('permission:orders.view')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
        Route::get('/cake-point', [CakePointController::class, 'index'])->name('cake-point.index');
        Route::get('/day-closes', [DayCloseController::class, 'index'])->name('day-closes.index');
        Route::post('/cake-point/orders/{sale}/status', [CakePointController::class, 'updateStatus'])->name('cake-point.update-status');
    });

    Route::middleware('permission:orders.manage')->group(function () {
        Route::get('/sales/{sale}/edit', [SaleController::class, 'edit'])->name('sales.edit');
        Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');
        Route::delete('/sales/{sale}', [SaleController::class, 'destroy'])->name('sales.destroy');
    });

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
        Route::get('/ingredients', [IngredientController::class, 'index'])->name('ingredients.index');
        Route::get('/receivings', [IngredientReceivingController::class, 'index'])->name('receivings.index');
    });

    Route::middleware('permission:inventory.manage')->group(function () {
        Route::get('/ingredients/create', [IngredientController::class, 'create'])->name('ingredients.create');
        Route::post('/ingredients', [IngredientController::class, 'store'])->name('ingredients.store');
        Route::get('/ingredients/{ingredient}/edit', [IngredientController::class, 'edit'])->name('ingredients.edit');
        Route::put('/ingredients/{ingredient}', [IngredientController::class, 'update'])->name('ingredients.update');
        Route::delete('/ingredients/{ingredient}', [IngredientController::class, 'destroy'])->name('ingredients.destroy');

        Route::get('/receivings/create', [IngredientReceivingController::class, 'create'])->name('receivings.create');
        Route::post('/receivings', [IngredientReceivingController::class, 'store'])->name('receivings.store');
        Route::delete('/receivings/{receiving}', [IngredientReceivingController::class, 'destroy'])->name('receivings.destroy');
    });

    Route::middleware('owner')->group(function () {
        Route::post('/impersonate/{staff}', [ImpersonationController::class, 'start'])->name('impersonate.start');
        Route::post('/cake-point/orders', [CakePointController::class, 'store'])->name('cake-point.store');
        Route::post('/sales/{sale}/assign', [CakePointController::class, 'assign'])->name('sales.assign');

        Route::resource('items', ItemController::class)->except(['show']);

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/business', [SettingsController::class, 'updateBusiness'])->name('settings.business.update');
        Route::put('/settings/appearance', [SettingsController::class, 'updateAppearance'])->name('settings.appearance.update');
        Route::put('/settings/sms', [SettingsController::class, 'updateSms'])->name('settings.sms.update');

        Route::post('/settings/categories/import', [CategoryController::class, 'importDefaults'])->name('settings.categories.import');
        Route::post('/settings/categories', [CategoryController::class, 'store'])->name('settings.categories.store');
        Route::put('/settings/categories/{category}', [CategoryController::class, 'update'])->name('settings.categories.update');
        Route::delete('/settings/categories/{category}', [CategoryController::class, 'destroy'])->name('settings.categories.destroy');

        Route::post('/settings/packages/import', [PackageUnitController::class, 'importDefaults'])->name('settings.packages.import');
        Route::post('/settings/packages', [PackageUnitController::class, 'store'])->name('settings.packages.store');
        Route::put('/settings/packages/{packageUnit}', [PackageUnitController::class, 'update'])->name('settings.packages.update');
        Route::delete('/settings/packages/{packageUnit}', [PackageUnitController::class, 'destroy'])->name('settings.packages.destroy');

        Route::post('/settings/payments/import', [PaymentProviderController::class, 'importDefaults'])->name('settings.payments.import');
        Route::post('/settings/payments', [PaymentProviderController::class, 'store'])->name('settings.payments.store');
        Route::put('/settings/payments/{paymentProvider}', [PaymentProviderController::class, 'update'])->name('settings.payments.update');
        Route::delete('/settings/payments/{paymentProvider}', [PaymentProviderController::class, 'destroy'])->name('settings.payments.destroy');

        Route::resource('roles', RoleController::class)->except(['show']);
        Route::resource('staff', StaffController::class)->except(['show']);
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
