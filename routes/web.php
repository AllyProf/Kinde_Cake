<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BusinessTrendsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DayCloseController;
use App\Http\Controllers\DayReportController;
use App\Http\Controllers\CakePointController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\IngredientReceivingController;
use App\Http\Controllers\IngredientUsageController;
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

Route::get('/', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/', [LoginController::class, 'login'])->middleware('guest');
Route::redirect('/login', '/');

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    });

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
    });

    Route::middleware('permission:expenses.view')->group(function () {
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
    });

    Route::middleware('permission:expenses.manage')->group(function () {
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
        Route::post('/day-closes/expenses', [DayCloseController::class, 'storeExpense'])->name('day-closes.expenses.store');
        Route::delete('/day-closes/expenses/{expense}', [DayCloseController::class, 'destroyExpense'])->name('day-closes.expenses.destroy');
    });

    Route::middleware('owner')->group(function () {
        Route::post('/day-closes', [DayCloseController::class, 'store'])->name('day-closes.store');
        Route::post('/impersonate/{staff}', [ImpersonationController::class, 'start'])->name('impersonate.start');
        Route::post('/cake-point/orders', [CakePointController::class, 'store'])->name('cake-point.store');
        Route::post('/sales/{sale}/assign', [CakePointController::class, 'assign'])->name('sales.assign');
    });

    Route::middleware('permission:reports.view')->group(function () {
        Route::get('/day-reports', [DayReportController::class, 'index'])->name('day-reports.index');
        Route::get('/business-trends', [BusinessTrendsController::class, 'index'])->name('business-trends.index');
    });

    Route::middleware('permission:orders.view')->group(function () {
        Route::get('/sales', [SaleController::class, 'index'])->name('sales.index');
        Route::get('/sales/{sale}', [SaleController::class, 'show'])->name('sales.show');
        Route::get('/customer-orders', [CustomerOrderController::class, 'index'])->name('customer-orders.index');
        Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
        Route::get('/cake-point', [CakePointController::class, 'index'])->name('cake-point.index');
        Route::get('/day-closes', [DayCloseController::class, 'index'])->name('day-closes.index');
        Route::post('/cake-point/orders/{sale}/status', [CakePointController::class, 'updateStatus'])->name('cake-point.update-status');

        Route::middleware('permission:sms.send')->group(function () {
            Route::post('/customer-orders/{sale}/notify-ready', [CustomerOrderController::class, 'notifyReady'])->name('customer-orders.notify-ready');
        });
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
        Route::get('/ingredient-usages', [IngredientUsageController::class, 'index'])->name('ingredient-usages.index');
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

        Route::post('/ingredient-usages', [IngredientUsageController::class, 'store'])->name('ingredient-usages.store');
    });

    Route::middleware('permission:catalog.view')->group(function () {
        Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    });

    Route::middleware('permission:catalog.manage')->group(function () {
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{item}/edit', [ItemController::class, 'edit'])->name('items.edit');
        Route::put('/items/{item}', [ItemController::class, 'update'])->name('items.update');
        Route::delete('/items/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    });

    Route::middleware('permission:settings.view')->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    });

    Route::middleware('permission:settings.manage')->group(function () {
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
    });

    Route::middleware('permission:roles.manage')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('permission:staff.view')->group(function () {
        Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
    });

    Route::middleware('permission:staff.manage')->group(function () {
        Route::get('/staff/create', [StaffController::class, 'create'])->name('staff.create');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::get('/staff/{staff}/edit', [StaffController::class, 'edit'])->name('staff.edit');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
        Route::post('/staff/{staff}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
    });

    Route::middleware('permission:audit.view')->group(function () {
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
