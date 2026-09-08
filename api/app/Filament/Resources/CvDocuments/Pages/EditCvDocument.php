<?php

namespace App\Filament\Resources\CvDocuments\Pages;

use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\CvDocuments\CvDocumentResource;
use App\Filament\Support\EditorialActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class EditCvDocument extends EditRecord
{
    protected static string $resource = CvDocumentResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            $this->removePdfAction(),
            DeleteAction::make()->using(function (Model $record): bool {
                app(DeleteContent::class)($record);

                return true;
            }),
        ];
    }

    private function removePdfAction(): Action
    {
        return Action::make('remove_pdf')
            ->label('Remove PDF')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => filled($this->getRecord()->private_path))
            ->action(function (): void {
                $updated = app(RemoveOwnedAsset::class)($this->getRecord());
                $this->syncRecord($updated);
                Notification::make()->success()->title('PDF removed')->send();
            });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Defense in depth: `locale` is disabled (and therefore not
        // dehydrated) on the form, but a tampered request could still
        // smuggle it in; `UpdateContent` rejects it outright regardless.
        unset($data['locale']);

        $pdf = $data['pdf'] ?? null;
        unset($data['pdf']);

        try {
            $updated = app(UpdateContent::class)($record, $data);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure($exception);

            throw new Halt;
        }

        if ($pdf instanceof UploadedFile) {
            $updated = app(ReplaceOwnedAsset::class)($updated, $pdf);
        }

        $this->record = $updated;

        if ($updated->status === PublicationStatus::Published && $updated->is_visible) {
            Notification::make()
                ->warning()
                ->title('Saved')
                ->body('This content is published and visible: the change is immediately public.')
                ->send();
        }

        return $updated;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        $record = $this->getRecord();

        if ($record->status === PublicationStatus::Published && $record->is_visible) {
            return null;
        }

        return 'Saved';
    }

    private function notifyValidationFailure(PublicationValidationException $exception): void
    {
        $body = collect($exception->issues())
            ->map(fn ($issue): string => "[{$issue->code}] {$issue->path}: {$issue->message}")
            ->implode("\n");

        Notification::make()
            ->danger()
            ->title('Save failed')
            ->body($body)
            ->send();
    }

    private function syncRecord(Model $updated): void
    {
        $this->record = $updated;
    }
}
