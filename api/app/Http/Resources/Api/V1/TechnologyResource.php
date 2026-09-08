<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Technology;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Technology */
final class TechnologyResource extends JsonResource
{
    /** @return array{key: string, name: string, category: string, icon: ?array{url: string}} */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'category' => $this->category->value,
            'icon' => $this->publicAsset($this->icon_public_path),
        ];
    }

    /** @return ?array{url: string} */
    private function publicAsset(?string $path): ?array
    {
        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return ['url' => '/storage/'.ltrim($path, '/')];
    }
}
