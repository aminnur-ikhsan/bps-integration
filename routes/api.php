<?php

use Illuminate\Support\Facades\Route;

// Prefix /api dan middleware group "api" sudah dipasang otomatis oleh
// bootstrap/app.php, jadi di sini cukup menambah versinya.
// client.log ditaruh di luar client.token supaya request yang ditolak 403
// pun ikut tercatat.
Route::prefix('v1')->middleware(['client.log', 'client.token'])->group(function () {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));
});
