<?php

use App\Http\Resources\ApiVersionResource;
use Illuminate\Support\Facades\Route;

Route::get('v1', static fn (): ApiVersionResource => new ApiVersionResource([
    'status' => 'ok',
    'version' => 'v1',
]))->middleware('throttle:public-api');
