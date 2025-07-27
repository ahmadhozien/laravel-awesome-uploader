<?php

use Illuminate\Support\Facades\Route;
use Hozien\Uploader\Http\Controllers\UploadController;

Route::group(['middleware' => 'web'], function () {
    Route::get('/uploader', [UploadController::class, 'show'])->name('uploader.show');
});

Route::group(['middleware' => ['api', 'json'], 'prefix' => 'api/uploader'], function () {
    // File upload endpoints
    Route::post('/upload', [UploadController::class, 'upload'])->name('uploader.upload');

    // File management endpoints  
    Route::get('/uploads', [UploadController::class, 'index'])->name('uploader.index');
    Route::delete('/uploads/{id}', [UploadController::class, 'destroy'])->name('uploader.destroy');
    Route::put('/uploads/{id}/rename', [UploadController::class, 'rename'])->name('uploader.rename');
    Route::get('/uploads/{id}/thumbnails', [UploadController::class, 'getThumbnails'])->name('uploader.thumbnails');

    // Statistics and management endpoints
    Route::get('/stats', [UploadController::class, 'stats'])->name('uploader.stats');
    Route::post('/cleanup', [UploadController::class, 'cleanup'])->name('uploader.cleanup');
});
