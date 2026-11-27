<?php

use App\Http\Controllers\Api\JapatiMirrorController;
use App\Http\Controllers\Api\SubjectCategoryController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\VariableController;
use Illuminate\Support\Facades\Route;

// client.log di luar client.token supaya request yang ditolak 403 tetap tercatat.
Route::prefix('v1')->middleware(['client.log', 'client.token'])->group(function () {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));

    Route::get('data-sampel', fn () => response()->json(['status' => 'ok data-sampel']))->middleware('saving_body_response');

    // Jalur mirror sementara: cabut atau ganti saat koneksi resmi Japati siap.
    // Path setelah prefix identik dengan API Japati asli.
    Route::prefix('japati-mirror')->group(function () {
        Route::get('option/satuan-kerja', [JapatiMirrorController::class, 'satuanKerja']);
        Route::get('public-display/display-mikro/cascading', [JapatiMirrorController::class, 'cascading']);
    });

    Route::prefix('{region}')->middleware('region')->group(function () {
        Route::get('subject-categories', [SubjectCategoryController::class, 'index']);
        Route::get('subjects', [SubjectController::class, 'index']);
        Route::get('variables', [VariableController::class, 'index']);
        Route::get('variables/{var_id}', [VariableController::class, 'show'])->whereNumber('var_id');
    });
});
