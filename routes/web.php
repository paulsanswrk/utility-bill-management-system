<?php

use App\Http\Controllers\BillController;
use App\Http\Controllers\HouseholdController;
use App\Http\Controllers\ManageUsersController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (Auth::user()->is_admin) {
        return redirect()->route('manage_users');
    }

    return Inertia::render('Dashboard', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->middleware(['auth', 'verified']);

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/information', function () {
    return Inertia::render('Information');
})->name('information');

Route::get('/users', function () {
    return Inertia::render('Users');
})->middleware(['auth', 'verified'])->name('manage_users');

Route::get('/ai-summaries', function () {
    return Inertia::render('AiSummaries');
})->middleware(['auth', 'verified'])->name('ai_summaries');

Route::get('/households', function () {
    return Inertia::render('HouseholdsAccess');
})->middleware(['auth', 'verified'])->name('households_access');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/zip', [BillController::class, 'zip'])->middleware(['auth', 'verified']);

Route::get('/clear-cache', function () {
    $exitCode = Artisan::call('cache:clear');
});

Route::get('/confirmemailchange/{uuid}', [ManageUsersController::class, 'change_email_confirmation']);

Route::get('/households/accept/{uuid}', [HouseholdController::class, 'accept']);
Route::get('/households/decline/{uuid}', [HouseholdController::class, 'decline']);


require __DIR__ . '/auth.php';
