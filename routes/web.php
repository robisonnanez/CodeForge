<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::inertia('apps/calendar', 'apps/calendar')->name('apps.calendar');
    Route::inertia('apps/chat', 'apps/chat')->name('apps.chat');
    Route::inertia('apps/mail', 'apps/mail')->name('apps.mail');
    Route::inertia('apps/task-list', 'apps/task-list')->name('apps.task-list');
});

require __DIR__.'/settings.php';
