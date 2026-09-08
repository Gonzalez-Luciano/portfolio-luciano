<?php

namespace App\Filament\Resources\Experiences\Pages;

use App\Domain\Content\Actions\ChangePublicKey;
use App\Domain\Content\Actions\DeleteContent;
use App\Domain\Content\Actions\UpdateExperienceAggregate;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Resources\Experiences\ExperienceResource;
use App\Filament\Support\EditorialActions;
use App\Models\Experience;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

class EditExperience extends EditRecord
{
    protected static string $resource = ExperienceResource::class;

    /**
     * Highlights and technologies are relations, not plain model
     * attributes, so `attributesToArray()` never carries them: they are
     * injected into the form's fill data here, in the same display order
     * the model relations already enforce (`highlights` by position/id,
     * `technologies` by pivot position/key).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Experience $record */
        $record = $this->getRecord();

        $data['highlights'] = $record->highlights->map(fn ($highlight): array => [
            'content_es' => $highlight->content_es,
            'content_en' => $highlight->content_en,
        ])->all();

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
                    ->unique(table: 'experiences', column: 'key', ignoreRecord: true),
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
     * Experience is an aggregate root: `ExperienceHighlight` rows and the
     * `Technology` pivot are never mutated directly, only through
     * `UpdateExperienceAggregate`, which replaces both lists wholesale and
     * commits them together with the parent's own attributes in a single
     * transaction. The repeater's submitted item order (already a plain,
     * zero-based list once Filament dehydrates it) becomes each item's
     * explicit `position`.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $highlightsInput = $data['highlights'] ?? [];
        $technologiesInput = $data['technologies'] ?? [];

        // Defense in depth: none of these are exposed as plain form fields
        // (key is disabled/not dehydrated on edit, position only changes
        // through the table's reorder action, and status/is_visible/
        // published_at/key_locked have no form fields at all), but a
        // tampered request could still smuggle them into the payload, and
        // highlights/technologies are passed to the aggregate action as
        // their own parameters, not as plain attributes.
        unset($data['highlights'], $data['technologies'], $data['key'], $data['key_locked'], $data['position'], $data['status'], $data['is_visible'], $data['published_at']);

        $highlights = [];
        foreach (array_values($highlightsInput) as $position => $highlight) {
            $highlights[] = [
                'content_es' => $highlight['content_es'] ?? null,
                'content_en' => $highlight['content_en'] ?? null,
                'position' => $position,
            ];
        }

        $technologies = [];
        foreach (array_values($technologiesInput) as $position => $technology) {
            $technologies[] = [
                'technology_id' => (int) $technology['technology_id'],
                'position' => $position,
            ];
        }

        try {
            /** @var Experience $updated */
            $updated = app(UpdateExperienceAggregate::class)($record, $data, $highlights, $technologies);
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
