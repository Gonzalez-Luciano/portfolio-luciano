<?php

namespace App\Filament\Resources\EducationEntries\Pages;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\EducationEntries\EducationEntryResource;
use App\Filament\Support\EditorialActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditEducationEntry extends EditRecord
{
    protected static string $resource = EducationEntryResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            $this->changeKeyAction(),
            DeleteAction::make()->using(function (Model $record): bool {
                app(DeleteContent::class)($record);

                return true;
            }),
        ];
    }

    private function changeKeyAction(): Action
    {
        return Action::make('change_key')
            ->label('Change key')
            ->color('warning')
            ->schema([
                TextInput::make('key')
                    ->label('New key')
                    ->required()
                    ->maxLength(100)
                    ->default(fn (Model $record): string => $record->key)
                    ->unique(table: 'education_entries', column: 'key', ignoreRecord: true),
            ])
            ->requiresConfirmation(fn (Model $record): bool => (bool) $record->key_locked)
            ->modalDescription('This key is already locked by a previous publication. Changing it affects any public references to it.')
            ->action(function (Model $record, array $data): void {
                try {
                    $updated = app(ChangePublicKey::class)($record, $data['key'], confirmed: true);
                } catch (PublicationValidationException $exception) {
                    $this->notifyValidationFailure('Change key', $exception);

                    return;
                } catch (\LogicException $exception) {
                    Notification::make()->danger()->title('Change key failed')->body($exception->getMessage())->send();

                    return;
                }

                $this->syncRecord($updated);
                Notification::make()->success()->title('Key changed')->send();
            });
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        unset($data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at']);

        foreach (['start_year', 'end_year'] as $year) {
            if (array_key_exists($year, $data) && ($data[$year] === '' || $data[$year] === null)) {
                $data[$year] = null;
            }
        }

        try {
            $updated = app(UpdateContent::class)($record, $data);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure('Save', $exception);

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

    private function notifyValidationFailure(string $label, PublicationValidationException $exception): void
    {
        $body = collect($exception->issues())
            ->map(fn ($issue): string => "[{$issue->code}] {$issue->path}: {$issue->message}")
            ->implode("\n");

        Notification::make()
            ->danger()
            ->title("{$label} failed")
            ->body($body)
            ->send();
    }

    private function syncRecord(Model $updated): void
    {
        $this->record = $updated;
    }
}
