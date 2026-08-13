<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use stdClass;

final class ApiErrorResponse extends JsonResponse
{
    /**
     * @param  array<string, array<int, string>>|object  $details
     */
    public static function make(string $code, string $message, array|object $details = [], int $status = 500): self
    {
        return new self([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details === [] ? new stdClass : (object) $details,
            ],
        ], $status);
    }
}
