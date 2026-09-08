<?php

use App\Http\Controllers\CvDownloadController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/cv/luciano-gonzalez-es.pdf', [CvDownloadController::class, 'es'])
    ->middleware('throttle:cv-download')
    ->name('cv.download.es');

Route::get('/cv/luciano-gonzalez-en.pdf', [CvDownloadController::class, 'en'])
    ->middleware('throttle:cv-download')
    ->name('cv.download.en');
