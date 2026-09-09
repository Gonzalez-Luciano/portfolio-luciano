<?php

namespace App\Filament\Resources\Technologies\Pages;

use App\Domain\Assets\AssetOperationException;
use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Technologies\TechnologyResource;
use App\Filament\Support\EditorialActions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;

class EditTechnology extends EditRecord
{
    protected static string $resource = TechnologyResource::class;

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            $this->changeKeyAction(),
            $this->removeIconAction(),
            $this->deleteAction(),
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
                    ->unique(table: 'technologies', column: 'key', ignoreRecord: true),
            ])
            ->requiresConfirmation(fn (Model $record): bool => (bool) $record->key_locked)
            ->modalDescription('This key is already locked by a previous publication. Changing it affects any public URLs that reference it.')
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

    private function removeIconAction(): Action
    {
        return Action::make('remove_icon')
            ->label('Remove icon')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => filled($this->getRecord()->icon_private_path))
            ->action(function (): void {
                try {
                    $updated = app(RemoveOwnedAsset::class)($this->getRecord());
                } catch (\Throwable $exception) {
                    EditorialActions::notifyAssetFailure('Remove icon', $exception);

                    return;
                }

                $this->syncRecord($updated);
                Notification::make()->success()->title('Icon removed')->send();
            });
    }

    /**
     * The DB-level `ON DELETE RESTRICT` foreign key on each technology
     * pivot's `technology_id` column (Task 1) is the actual enforcement
     * that a technology in use cannot be deleted; `DeleteContent` surfaces
     * that as an `AssetOperationException` wrapping the driver's
     * `QueryException`. That is translated here into a clear, controlled
     * notification instead of letting a raw SQL exception reach the UI.
     */
    private function deleteAction(): DeleteAction
    {
        return DeleteAction::make()->using(function (Model $record): bool {
            try {
                app(DeleteContent::class)($record);
            } catch (AssetOperationException $exception) {
                if ($exception->getPrevious() instanceof QueryException) {
                    Notification::make()
                        ->danger()
                        ->title('Delete failed')
                        ->body('This technology is still linked to at least one experience, work case, or project. Remove those relations first.')
                        ->send();

                    throw new Halt;
                }

                throw $exception;
            }

            return true;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Defense in depth: `key`/`key_locked`/`position` and the
        // publication attributes are already protected by `UpdateContent`
        // (and `key` is also disabled/not dehydrated on the form), but a
        // tampered request could still smuggle them into the payload.
        unset($data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at']);

        $icon = $data['icon'] ?? null;
        unset($data['icon']);

        try {
            $updated = app(UpdateContent::class)($record, $data);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure('Save', $exception);

            throw new Halt;
        }

        if ($icon instanceof UploadedFile) {
            try {
                $updated = app(ReplaceOwnedAsset::class)($updated, $icon);
            } catch (\Throwable $exception) {
                EditorialActions::notifyAssetFailure('Save', $exception);

                throw new Halt;
            }
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
