<?php

use Illuminate\Support\Facades\Route;
use Hozien\Uploader\Http\Controllers\UploadController;

Route::group(['middleware' => 'web'], function () {
    Route::get('/uploader', [UploadController::class, 'show'])->name('uploader.show');
});

Route::group(['middleware' => 'api', 'prefix' => 'api'], function () {
    Route::post('/upload', [UploadController::class, 'upload'])->name('uploader.upload');
    Route::get('/uploads', [UploadController::class, 'index'])->name('uploader.index');
    Route::delete('/uploads/{id}', [UploadController::class, 'destroy'])->name('uploader.destroy');
});
