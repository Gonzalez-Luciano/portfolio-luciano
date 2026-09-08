<?php

use App\Http\Controllers\Api\V1\ExperienceController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\SiteController;
use App\Http\Controllers\Api\V1\TechnologyController;
use App\Http\Controllers\Api\V1\WorkCaseController;
use App\Http\Resources\ApiVersionResource;
use Illuminate\Support\Facades\Route;

Route::get('v1', static fn (): ApiVersionResource => new ApiVersionResource([
    'status' => 'ok',
    'version' => 'v1',
]))->middleware('throttle:public-api');

Route::prefix('v1/{locale}')->middleware(['throttle:public-api', 'supported-locale'])->group(function (): void {
    Route::get('profile', ProfileController::class);
    Route::get('experiences', ExperienceController::class);
    Route::get('work-cases', WorkCaseController::class);
    Route::get('projects', ProjectController::class);
    Route::get('technologies', TechnologyController::class);
    Route::get('site', SiteController::class);
});
