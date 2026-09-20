<?php

use App\Http\Controllers\Api\SubjectCategoryController;
use Illuminate\Support\Facades\Route;

// client.log di luar client.token supaya request yang ditolak 403 tetap tercatat.
Route::prefix('v1')->middleware(['client.log', 'client.token'])->group(function () {
    Route::get('ping', fn () => response()->json(['status' => 'ok']));

    Route::get('data-sampel', fn () => response()->json(['status' => 'ok data-sampel']))->middleware('saving_body_response');

    Route::prefix('jawa-barat')->group(function () {
        Route::get('subject-categories', [SubjectCategoryController::class, 'index'])
            ->defaults('domain_id', '3200');
    });
});
