<?php

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
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $isApiPath = static fn (Request $request): bool => $request->is('api') || $request->is('api/*');

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $isApiPath($request) || $request->expectsJson(),
        );

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
