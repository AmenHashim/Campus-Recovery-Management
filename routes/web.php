<?php

use App\Http\Controllers\Admin\AnalyticsController as AdminAnalyticsController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReferenceController as AdminReferenceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Officer\ClaimController as OfficerClaimController;
use App\Http\Controllers\Officer\DashboardController as OfficerDashboardController;
use App\Http\Controllers\Officer\ItemController as OfficerItemController;
use App\Http\Controllers\Officer\NotificationController as OfficerNotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchSuggestionController;
use App\Http\Controllers\Student\ClaimController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\ItemController;
use App\Http\Controllers\Student\NotificationController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

/*
|--------------------------------------------------------------------------
| CPRMS Routes — grouped by role (FR-A4)
|--------------------------------------------------------------------------
| 'auth'   → must be logged in
| 'active' → account not suspended (FR-F1)
| 'role:x' → Spatie role gate
|
| The dashboard closures below are placeholders for Sprint 1. They get replaced
| by real controllers as each later sprint delivers its module.
*/

Route::get('/', function () {
    return auth()->check() 
    ? redirect()->route(auth()->user()->homeRoute()) : view('welcome');})->name('home');

/* ───────────────── STUDENT / STAFF ───────────────── */
Route::middleware(['auth', 'active', 'role:'.User::ROLE_STUDENT_STAFF])
    ->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');

        Route::get('/report', [ItemController::class, 'create'])->name('items.create');
        Route::post('/report', [ItemController::class, 'store'])->name('items.store');
        Route::get('/browse', [ItemController::class, 'index'])->name('items.index');
        Route::get('/browse/suggestions', [SearchSuggestionController::class, 'browseItems'])->name('items.suggest');
        Route::get('/my-reports', [ItemController::class, 'mine'])->name('items.mine');
        Route::get('/my-reports/suggestions', [SearchSuggestionController::class, 'myItems'])->name('items.mine.suggest');

        Route::post('/items/{item}/claim', [ClaimController::class, 'store'])->name('claims.store');
        Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');

        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    });

/* ───────────────── LOST & FOUND OFFICER ───────────────── */
Route::middleware(['auth', 'active', 'role:'.User::ROLE_OFFICER])
    ->prefix('officer')->name('officer.')->group(function () {
        Route::get('/dashboard', [OfficerDashboardController::class, 'index'])->name('dashboard');

        Route::get('/guest-reports', [OfficerItemController::class, 'create'])->name('guest-reports.create');
        Route::post('/guest-reports', [OfficerItemController::class, 'store'])->name('guest-reports.store');

        Route::get('/intake', [OfficerItemController::class, 'intake'])->name('intake.index');
        Route::post('/intake/{item}/return', [OfficerItemController::class, 'markReturned'])->name('intake.return');
        Route::post('/intake/{item}/close', [OfficerItemController::class, 'close'])->name('intake.close');

        Route::get('/claims', [OfficerClaimController::class, 'index'])->name('claims.index');
        Route::get('/claims/suggestions', [SearchSuggestionController::class, 'claims'])->name('claims.suggest');
        Route::post('/claims/{claim}/verify', [OfficerClaimController::class, 'verify'])->name('claims.verify');
        Route::post('/claims/{claim}/reject', [OfficerClaimController::class, 'reject'])->name('claims.reject');

        Route::get('/notifications', [OfficerNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/{notification}/read', [OfficerNotificationController::class, 'markRead'])->name('notifications.read');
    });

/* ───────────────── SUPER ADMIN ───────────────── */
Route::middleware(['auth', 'active', 'role:'.User::ROLE_ADMIN])
    ->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/users/suggestions', [SearchSuggestionController::class, 'users'])->name('users.suggest');
        Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::post('/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('users.role');
        // Soft delete only — the row and its history stay in the database (FR-F1).
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/{user}/restore', [AdminUserController::class, 'restore'])
            ->withTrashed()->name('users.restore');

        Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics.index');

        Route::get('/reference', [AdminReferenceController::class, 'index'])->name('reference.index');
        Route::post('/reference/categories', [AdminReferenceController::class, 'storeCategory'])->name('reference.categories.store');
        Route::put('/reference/categories/{category}', [AdminReferenceController::class, 'updateCategory'])->name('reference.categories.update');
        Route::post('/reference/categories/{category}/toggle', [AdminReferenceController::class, 'toggleCategory'])->name('reference.categories.toggle');
        Route::delete('/reference/categories/{category}', [AdminReferenceController::class, 'destroyCategory'])->name('reference.categories.destroy');
        Route::post('/reference/locations', [AdminReferenceController::class, 'storeLocation'])->name('reference.locations.store');
        Route::put('/reference/locations/{location}', [AdminReferenceController::class, 'updateLocation'])->name('reference.locations.update');
        Route::post('/reference/locations/{location}/toggle', [AdminReferenceController::class, 'toggleLocation'])->name('reference.locations.toggle');
        Route::delete('/reference/locations/{location}', [AdminReferenceController::class, 'destroyLocation'])->name('reference.locations.destroy');

        Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
        Route::get('/audit/suggestions', [SearchSuggestionController::class, 'auditLogs'])->name('audit.suggest');
    });

/* ───────────────── SHARED (any authenticated, active user) ───────────────── */
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // No self-deletion: account removal is an admin action (admin.users.destroy).
});


require __DIR__.'/auth.php';
