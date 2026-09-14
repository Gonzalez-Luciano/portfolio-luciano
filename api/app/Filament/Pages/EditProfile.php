<?php

namespace App\Filament\Pages;

use App\Domain\Content\Actions\RemoveOwnedAsset;
use App\Domain\Content\Actions\ReplaceOwnedAsset;
use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Content\Actions\UpdateOwnedAssetAltText;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Support\EditorialActions;
use App\Filament\Support\ReviewLink;
use App\Models\Profile;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

final class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Profile';

    protected static \UnitEnum|string|null $navigationGroup = 'Profile and site';

    protected string $view = 'filament.pages.edit-profile';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?Profile $record = null;

    public function getTitle(): string
    {
        return 'Profile';
    }

    public function mount(): void
    {
        // Defense against exceptional manual deletion of the structural
        // singleton row. Migrations remain the normal structural guarantee;
        // this ensure is idempotent and never runs from a public GET.
        DB::table('profiles')->insertOrIgnore([
            'singleton_key' => 'default',
            'status' => PublicationStatus::Draft->value,
            'is_visible' => false,
            'published_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->record = Profile::query()->where('singleton_key', 'default')->firstOrFail();

        $this->fillFormFromRecord();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Name')
                    ->maxLength(255)
                    ->helperText('Required to publish. Not required to save a draft.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('headline_es')->label('Headline (ES)')->maxLength(10000),
                        Textarea::make('short_summary_es')->label('Short summary (ES)')->maxLength(10000),
                        Textarea::make('introduction_es')->label('Introduction (ES)')->maxLength(10000),
                        TextInput::make('availability_es')->label('Availability (ES)')->maxLength(10000),
                        TextInput::make('statement_lead_es')->label('Scroll statement: lead (ES)')->maxLength(10000)
                            ->helperText('Optional. Lead, emphasis, and tail are shown together only when all three are filled.'),
                        TextInput::make('statement_emphasis_es')->label('Scroll statement: emphasis (ES)')->maxLength(10000),
                        TextInput::make('statement_tail_es')->label('Scroll statement: tail (ES)')->maxLength(10000),
                        TextInput::make('closing_line_one_es')->label('Closing title: line one (ES)')->maxLength(10000)
                            ->helperText('Optional. Both lines are shown only when both are filled.'),
                        TextInput::make('closing_line_two_es')->label('Closing title: line two (ES)')->maxLength(10000),
                        TextInput::make('cta_es')->label('Call to action (ES)')->maxLength(255),
                        TextInput::make('photo_alt_es')->label('Photo alt text (ES)')->maxLength(500),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('headline_en')->label('Headline (EN)')->maxLength(10000),
                        Textarea::make('short_summary_en')->label('Short summary (EN)')->maxLength(10000),
                        Textarea::make('introduction_en')->label('Introduction (EN)')->maxLength(10000),
                        TextInput::make('availability_en')->label('Availability (EN)')->maxLength(10000),
                        TextInput::make('statement_lead_en')->label('Scroll statement: lead (EN)')->maxLength(10000)
                            ->helperText('Optional. Lead, emphasis, and tail are shown together only when all three are filled.'),
                        TextInput::make('statement_emphasis_en')->label('Scroll statement: emphasis (EN)')->maxLength(10000),
                        TextInput::make('statement_tail_en')->label('Scroll statement: tail (EN)')->maxLength(10000),
                        TextInput::make('closing_line_one_en')->label('Closing title: line one (EN)')->maxLength(10000)
                            ->helperText('Optional. Both lines are shown only when both are filled.'),
                        TextInput::make('closing_line_two_en')->label('Closing title: line two (EN)')->maxLength(10000),
                        TextInput::make('cta_en')->label('Call to action (EN)')->maxLength(255),
                        TextInput::make('photo_alt_en')->label('Photo alt text (EN)')->maxLength(500),
                    ]),
                ]),

                FileUpload::make('photo')
                    ->label('Photo')
                    ->image()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(5 * 1024)
                    ->helperText('JPEG, PNG, or WebP, up to 5 MiB. Uploading a new photo replaces the current one only after saving.'),

                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (): string => ucfirst($this->record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (): string => ($this->record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (): string => $this->record?->published_at?->toDateTimeString() ?? 'Never'),
            ])
            ->statePath('data');
    }

    /** @return array<Action> */
    protected function getHeaderActions(): array
    {
        return [
            ReviewLink::action(fn (): Profile => $this->record),
            EditorialActions::publish(fn (): Profile => $this->record, $this->syncRecord(...)),
            EditorialActions::show(fn (): Profile => $this->record, $this->syncRecord(...)),
            EditorialActions::hide(fn (): Profile => $this->record, $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): Profile => $this->record, $this->syncRecord(...)),
            $this->removePhotoAction(),
        ];
    }

    private function removePhotoAction(): Action
    {
        return Action::make('remove_photo')
            ->label('Remove photo')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (): bool => filled($this->record?->photo_private_path))
            ->action(function (): void {
                try {
                    $updated = app(RemoveOwnedAsset::class)($this->record);
                } catch (\Throwable $exception) {
                    EditorialActions::notifyAssetFailure('Remove photo', $exception);

                    return;
                }

                $this->syncRecord($updated);
                Notification::make()->success()->title('Photo removed')->send();
            });
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $photo = $state['photo'] ?? null;
        unset($state['photo']);

        $altEs = $state['photo_alt_es'] ?? null;
        $altEn = $state['photo_alt_en'] ?? null;
        unset($state['photo_alt_es'], $state['photo_alt_en']);

        try {
            $updated = app(UpdateContent::class)($this->record, $state);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure($exception);

            return;
        }

        if ($photo instanceof UploadedFile) {
            try {
                $updated = app(ReplaceOwnedAsset::class)($updated, $photo);
            } catch (\Throwable $exception) {
                EditorialActions::notifyAssetFailure('Save', $exception);

                return;
            }
        }

        if ($altEs !== $updated->photo_alt_es || $altEn !== $updated->photo_alt_en) {
            try {
                $updated = app(UpdateOwnedAssetAltText::class)($updated, $altEs, $altEn);
            } catch (\Throwable $exception) {
                Notification::make()
                    ->danger()
                    ->title('Save failed')
                    ->body($exception->getMessage())
                    ->send();

                return;
            }
        }

        $this->syncRecord($updated);

        if ($updated->status === PublicationStatus::Published && $updated->is_visible) {
            Notification::make()
                ->warning()
                ->title('Saved')
                ->body('This content is published and visible: the change is immediately public.')
                ->send();

            return;
        }

        Notification::make()->success()->title('Saved')->send();
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

    private function syncRecord(Profile $updated): void
    {
        $this->record = $updated;
        $this->fillFormFromRecord();
    }

    private function fillFormFromRecord(): void
    {
        $attributes = $this->record->attributesToArray();
        unset($attributes['status'], $attributes['is_visible'], $attributes['published_at']);
        $this->form->fill($attributes);
    }
}
