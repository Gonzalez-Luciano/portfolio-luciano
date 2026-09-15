<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\SyncProjectImages;
use App\Domain\Content\Actions\UpdateContentWithTechnologies;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Projects\Schemas\ProjectForm;
use App\Filament\Support\EditorialActions;
use App\Models\Project;
use App\Models\ProjectImage;
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
     * already enforces (pivot position, then key).
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

        $data['images'] = $record->images->map(fn (ProjectImage $image): array => [
            'id' => $image->getKey(),
            'file' => null,
            'alt_es' => $image->alt_es,
            'alt_en' => $image->alt_en,
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

    /**
     * `technologies` is never mutated directly, only through
     * `UpdateContentWithTechnologies`, which replaces the pivot wholesale
     * and commits it together with the parent's own plain attributes in a
     * single transaction. The repeater's submitted item order becomes each
     * item's explicit `position`.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Failing loudly on a missing key, rather than defaulting to an empty
        // list, guards against a future hidden()/dehydrated() change silently
        // deleting every technology.
        if (! array_key_exists('technologies', $data)) {
            throw new \LogicException('The project form did not submit its technologies state.');
        }
        $technologiesInput = $data['technologies'];
        $imagesInput = $data['images'] ?? null;

        // Defense in depth against a tampered payload: none of these are
        // plain editable attributes on this page.
        unset(
            $data['technologies'], $data['images'],
            $data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at'],
        );

        // A hidden client field is not dehydrated, so switching to a personal
        // project must clear the stored client explicitly (DB check
        // projects_client_name_kind_check).
        if (! ProjectForm::isClient($data['kind'] ?? $record->kind)) {
            $data['client_name'] = null;
        }

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

        if (is_array($imagesInput)) {
            try {
                $updated = app(SyncProjectImages::class)($updated, self::galleryItems($imagesInput));
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

    /**
     * Normalizes the repeater state into the gallery service contract. A
     * FileUpload may dehydrate a single file either bare or wrapped in a
     * one-item array.
     *
     * @param  array<array-key, array<string, mixed>>  $input
     * @return list<array{id: ?int, upload: ?UploadedFile, alt_es: ?string, alt_en: ?string}>
     */
    private static function galleryItems(array $input): array
    {
        return array_values(array_map(static function (array $item): array {
            $file = $item['file'] ?? null;
            if (is_array($file)) {
                $file = array_values($file)[0] ?? null;
            }

            return [
                'id' => filled($item['id'] ?? null) ? (int) $item['id'] : null,
                'upload' => $file instanceof UploadedFile ? $file : null,
                'alt_es' => $item['alt_es'] ?? null,
                'alt_en' => $item['alt_en'] ?? null,
            ];
        }, $input));
    }
}
