<?php

namespace App\Providers;

use App\Http\Responses\ApiErrorResponse;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('public-api', static fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->ip())
            ->response(static fn (Request $request, array $headers): ApiErrorResponse => ApiErrorResponse::make(
                code: 'rate_limited',
                message: 'Too many requests. Please try again later.',
                details: [],
                status: 429,
            )->withHeaders($headers)));
    }
}
