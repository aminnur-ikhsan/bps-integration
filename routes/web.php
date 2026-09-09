<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('bps/domains', 'pages::bps.domains')->name('bps.domains');
    Route::livewire('bps/subject-categories', 'pages::bps.subject-categories')->name('bps.subject-categories');
    Route::livewire('bps/subjects', 'pages::bps.subjects')->name('bps.subjects');

    Route::livewire('bps/dynamic-data/variables', 'pages::bps.dynamic-data.variables')->name('bps.dynamic-data.variables');
    Route::livewire('bps/dynamic-data/vertical-variables', 'pages::bps.dynamic-data.vertical-variables')->name('bps.dynamic-data.vertical-variables');
    Route::livewire('bps/dynamic-data/derived-variables', 'pages::bps.dynamic-data.derived-variables')->name('bps.dynamic-data.derived-variables');
    Route::livewire('bps/dynamic-data/periods', 'pages::bps.dynamic-data.periods')->name('bps.dynamic-data.periods');
    Route::livewire('bps/dynamic-data/derived-periods', 'pages::bps.dynamic-data.derived-periods')->name('bps.dynamic-data.derived-periods');
    Route::livewire('bps/dynamic-data/units', 'pages::bps.dynamic-data.units')->name('bps.dynamic-data.units');
});

require __DIR__.'/settings.php';
