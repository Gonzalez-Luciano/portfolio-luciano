<?php

namespace App\Filament\Resources\CvDocuments\Pages;

use App\Enums\PublicationStatus;
use App\Filament\Resources\CvDocuments\CvDocumentResource;
use App\Models\CvDocument;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCvDocument extends CreateRecord
{
    protected static string $resource = CvDocumentResource::class;

    /**
     * Plain Eloquent create is permitted here: the editorial mutation guard
     * only forbids creating already-published content, and a brand-new
     * record has no sensitive editorial state to protect yet. The `locale`
     * identity is chosen once here (limited to a locale without an
     * existing row by the form's own select options) and is never
     * editable again.
     *
     * The PDF upload is discarded here (the owned-asset action requires an
     * existing CvDocument row) and is instead managed afterward on the
     * edit page. There is no `key`/`key_locked`/`position` to default:
     * this resource has none.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        unset($data['pdf']);
        $data['status'] = PublicationStatus::Draft;
        $data['is_visible'] = false;
        $data['published_at'] = null;

        return CvDocument::query()->create($data);
    }
}
