<?php

namespace App\Filament\Resources\CvDocuments\Pages;

use App\Filament\Resources\CvDocuments\CvDocumentResource;
use App\Models\CvDocument;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCvDocuments extends ListRecords
{
    protected static string $resource = CvDocumentResource::class;

    /**
     * At most two physical rows can ever exist (one per supported locale).
     * Once both locales have a row, creation is entirely hidden rather than
     * merely disabled: there is nothing left to create.
     *
     * @return array<CreateAction>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => CvDocument::query()->count() < 2),
        ];
    }
}
