<?php

namespace App\Http\Middleware;

use App\Enums\SupportedLocale;
use App\Http\Responses\ApiErrorResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireSupportedLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = SupportedLocale::tryFrom((string) $request->route('locale'));

        if ($locale === null) {
            return ApiErrorResponse::make(
                code: 'unsupported_locale',
                message: 'The requested locale is not supported.',
                details: [],
                status: 404,
            );
        }

        $request->attributes->set('supported_locale', $locale);

        return $next($request);
    }
}
