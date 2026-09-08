<?php

namespace App\Filament\Resources\ProfessionalLinks\Pages;

use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\ProfessionalLinks\ProfessionalLinkResource;
use App\Filament\Support\EditorialActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditProfessionalLink extends EditRecord
{
    protected static string $resource = ProfessionalLinkResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            DeleteAction::make()->using(function (Model $record): bool {
                app(DeleteContent::class)($record);

                return true;
            }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Defense in depth: the `type` field is disabled (and therefore not
        // dehydrated) on the form, and `position` only changes through the
        // table's reorder action, but a tampered request could still smuggle
        // either into the submitted payload, so both are stripped here
        // before ever reaching the domain action.
        unset($data['type'], $data['position']);

        try {
            $updated = app(UpdateContent::class)($record, $data);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure($exception);

            throw new Halt;
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
