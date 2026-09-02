<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('bps/domains', 'pages::bps.domains')->name('bps.domains');
});

require __DIR__.'/settings.php';
