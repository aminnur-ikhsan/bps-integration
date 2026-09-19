<?php

use Illuminate\Support\Facades\Route;

// Prefix /api dan middleware group "api" sudah dipasang otomatis oleh
// bootstrap/app.php, jadi di sini cukup menambah versinya.
Route::prefix('v1')->middleware(['client.token'])->group(function () {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));
});
