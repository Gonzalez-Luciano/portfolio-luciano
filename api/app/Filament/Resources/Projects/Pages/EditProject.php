<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Content\Actions\UpdateContentWithTechnologies;
use App\Domain\Content\Actions\UpdateOwnedAssetAltText;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Support\EditorialActions;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    /**
     * Technologies are a relation, not a plain model attribute, so
     * `attributesToArray()` never carries it: it is injected into the
     * form's fill data here, in the same display order the model relation
     * already enforces (pivot position, then key). The image upload field
     * itself is never prefilled from the existing owned asset (matching
     * `EditProfile`'s photo field): it starts empty and only replaces the
     * current image when a new file is submitted.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Project $record */
        $record = $this->getRecord();

        $data['technologies'] = $record->technologies->map(fn ($technology): array => [
            'technology_id' => $technology->getKey(),
        ])->all();

        return $data;
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            EditorialActions::publish(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::show(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::hide(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Model => $this->getRecord(), $this->syncRecord(...)),
            $this->changeKeyAction(),
            $this->removeImageAction(),
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
                    ->unique(table: 'projects', column: 'key', ignoreRecord: true),
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

    private function removeImageAction(): Action
    {
        return Action::make('remove_image')
            ->label('Remove image')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => filled($this->getRecord()->image_private_path))
            ->action(function (): void {
                try {
                    $updated = app(RemoveOwnedAsset::class)($this->getRecord());
                } catch (\Throwable $exception) {
                    EditorialActions::notifyAssetFailure('Remove image', $exception);

                    return;
                }

                $this->syncRecord($updated);
                Notification::make()->success()->title('Image removed')->send();
            });
    }

    /**
     * `technologies` is never mutated directly, only through
     * `UpdateContentWithTechnologies`, which replaces the pivot wholesale
     * and commits it together with the parent's own plain attributes in a
     * single transaction. The repeater's submitted item order (already a
     * plain, zero-based list once Filament dehydrates it) becomes each
     * item's explicit `position`. The image upload and its alt text are
     * owned-asset state, so - matching `EditProfile` exactly - they are
     * extracted first and, once the plain-attribute/technology update has
     * committed, applied afterward through their own dedicated actions.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // The repeater is always dehydrated on this page (its
        // `dehydrated()` closure only ever returns false on Create, where
        // this method is never called), so this key must always be
        // present here. Failing loudly on a missing key, rather than
        // silently defaulting to an empty list, guards against a future
        // change to the repeater's `hidden()`/`dehydrated()` conditions
        // silently turning into data loss: `UpdateContentWithTechnologies`
        // treats an empty list as "delete every technology", so a missing
        // key must never be mistaken for "nothing changed".
        if (! array_key_exists('technologies', $data)) {
            throw new \LogicException('The project form did not submit its technologies state.');
        }
        $technologiesInput = $data['technologies'];

        $photo = $data['image'] ?? null;
        $altEs = $data['image_alt_es'] ?? null;
        $altEn = $data['image_alt_en'] ?? null;

        // Defense in depth: none of these are exposed as plain form fields
        // that flow through the domain action below (key is disabled/not
        // dehydrated on edit, position only changes through the table's
        // reorder action, status/is_visible/published_at/key_locked have no
        // form fields at all, and the image/alt-text fields are handled
        // through their own dedicated owned-asset actions), but a tampered
        // request could still smuggle them into the payload.
        unset(
            $data['technologies'], $data['image'], $data['image_alt_es'], $data['image_alt_en'],
            $data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at'],
        );

        $technologies = [];
        foreach (array_values($technologiesInput) as $position => $technology) {
            $technologies[] = [
                'technology_id' => (int) $technology['technology_id'],
                'position' => $position,
            ];
        }

        try {
            /** @var Project $updated */
            $updated = app(UpdateContentWithTechnologies::class)($record, $data, $technologies);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure('Save', $exception);

            throw new Halt;
        }

        if ($photo instanceof UploadedFile) {
            try {
                $updated = app(ReplaceOwnedAsset::class)($updated, $photo);
            } catch (\Throwable $exception) {
                EditorialActions::notifyAssetFailure('Save', $exception);

                throw new Halt;
            }
        }

        if ($altEs !== $updated->image_alt_es || $altEn !== $updated->image_alt_en) {
            try {
                $updated = app(UpdateOwnedAssetAltText::class)($updated, $altEs, $altEn);
            } catch (\Throwable $exception) {
                Notification::make()->danger()->title('Save failed')->body($exception->getMessage())->send();

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
