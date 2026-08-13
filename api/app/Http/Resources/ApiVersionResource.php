<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array{status: string, version: string} */
final class ApiVersionResource extends JsonResource
{
    /**
     * @return array{status: string, version: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'status' => $this['status'],
            'version' => $this['version'],
        ];
    }
}
