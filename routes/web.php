<?php

use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

Route::middleware(['auth', 'verified', 'can:admin.access', 'staff.mfa'])
    ->prefix('admin')
    ->name('admin.')
    ->group(__DIR__.'/admin.php');
