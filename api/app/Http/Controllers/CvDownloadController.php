<?php

namespace App\Http\Controllers;

use App\Enums\SupportedLocale;
use App\Models\CvDocument;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CvDownloadController extends Controller
{
    public function es(): StreamedResponse
    {
        return $this->stream(SupportedLocale::Spanish);
    }

    public function en(): StreamedResponse
    {
        return $this->stream(SupportedLocale::English);
    }

    private function stream(SupportedLocale $locale): StreamedResponse
    {
        $cv = CvDocument::query()->publiclyAvailable()->where('locale', $locale)->first();

        abort_unless($cv !== null && Storage::disk('local')->exists($cv->private_path), 404);

        return Storage::disk('local')->download($cv->private_path, "luciano-gonzalez-{$locale->value}.pdf", [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
