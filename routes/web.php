<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('bps/domains', 'pages::bps.domains')->name('bps.domains');
    Route::livewire('bps/subject-categories', 'pages::bps.subject-categories')->name('bps.subject-categories');
    Route::livewire('bps/subjects', 'pages::bps.subjects')->name('bps.subjects');
});

require __DIR__.'/settings.php';
