<?php

use App\Exceptions\PublicContentUnavailable;
use App\Http\Middleware\RequireSupportedLocale;
use App\Http\Responses\ApiErrorResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'supported-locale' => RequireSupportedLocale::class,
        ]);

        // In production every request crosses two reverse proxies before it
        // reaches Laravel: the VPS-level global Caddy that terminates TLS and
        // the portfolio's own internal gateway. Without trusting them Laravel
        // sees plain HTTP from a container address and generates http:// URLs,
        // which breaks Filament's administration assets and every redirect.
        //
        // Only private ranges are trusted. The API publishes no host port and
        // is reachable exclusively through the gateway, so a public client can
        // never present one of these addresses; Symfony then walks
        // X-Forwarded-For right to left and stops at the first untrusted hop,
        // which keeps the request IP used by the rate limiters unspoofable.
        //
        // A wildcard is rejected outright: trusting every proxy would turn all
        // of the headers below into client-controlled input.
        $middleware->trustProxies(
            at: array_values(array_filter(
                array_map(trim(...), explode(',', (string) env(
                    'TRUSTED_PROXIES',
                    '10.0.0.0/8,172.16.0.0/12,192.168.0.0/16,127.0.0.0/8',
                ))),
                static fn (string $proxy): bool => $proxy !== '' && ! str_contains($proxy, '*'),
            )),
            // X-Forwarded-Host is deliberately NOT trusted. Caddy forwards the
            // original Host header, so Laravel already sees the real hostname,
            // and trusting the forwarded variant would let a visitor poison
            // generated URLs through a proxy that merely passes the header on.
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiPath = static fn (Request $request): bool => $request->is('api') || $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $isApiPath($request) || $request->expectsJson(),
        );

        $exceptions->render(function (PublicContentUnavailable $exception, Request $request) use ($isApiPath): ?ApiErrorResponse {
            if (! $isApiPath($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                code: 'content_temporarily_unavailable',
                message: 'Public content is temporarily unavailable. Please try again later.',
                details: [],
                status: 503,
            );
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) use ($isApiPath): ?ApiErrorResponse {
            if (! $isApiPath($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                code: 'not_found',
                message: 'The requested API resource was not found.',
                details: [],
                status: 404,
            );
        });

        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) use ($isApiPath): ?ApiErrorResponse {
            if (! $isApiPath($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                code: 'method_not_allowed',
                message: 'The requested HTTP method is not allowed.',
                details: [],
                status: 405,
            )->withHeaders($exception->getHeaders());
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) use ($isApiPath): ?ApiErrorResponse {
            if (! $isApiPath($request)) {
                return null;
            }

            return ApiErrorResponse::make(
                code: 'http_error',
                message: 'The API request could not be completed.',
                details: [],
                status: $exception->getStatusCode(),
            );
        });
    })->create();
