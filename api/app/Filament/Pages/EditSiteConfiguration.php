<?php

namespace App\Filament\Pages;

use App\Domain\Content\Actions\UpdateContent;
use App\Domain\Publishing\PublicationValidationException;
use App\Enums\PublicationStatus;
use App\Filament\Support\EditorialActions;
use App\Models\SiteConfiguration;
use Filament\Actions\Action;
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
use Illuminate\Support\Facades\DB;

final class EditSiteConfiguration extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationLabel = 'Site configuration';

    protected static \UnitEnum|string|null $navigationGroup = 'Profile and site';

    protected string $view = 'filament.pages.edit-site-configuration';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public ?SiteConfiguration $record = null;

    public function getTitle(): string
    {
        return 'Site configuration';
    }

    public function mount(): void
    {
        // Defense against exceptional manual deletion of the structural
        // singleton row. Migrations remain the normal structural guarantee;
        // this ensure is idempotent and never runs from a public GET.
        DB::table('site_configurations')->insertOrIgnore([
            'singleton_key' => 'default',
            'status' => PublicationStatus::Draft->value,
            'is_visible' => false,
            'published_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->record = SiteConfiguration::query()->where('singleton_key', 'default')->firstOrFail();

        $this->fillFormFromRecord();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        Textarea::make('projects_empty_message_es')->label('Projects empty message (ES)'),
                        Textarea::make('contact_intro_es')->label('Contact intro (ES)'),
                        TextInput::make('technology_backend_label_es')->label('Backend label (ES)'),
                        TextInput::make('technology_data_label_es')->label('Data label (ES)'),
                        TextInput::make('technology_integration_label_es')->label('Integration label (ES)'),
                        TextInput::make('technology_collaboration_label_es')->label('Collaboration label (ES)'),
                    ]),
                    Tab::make('English')->schema([
                        Textarea::make('projects_empty_message_en')->label('Projects empty message (EN)'),
                        Textarea::make('contact_intro_en')->label('Contact intro (EN)'),
                        TextInput::make('technology_backend_label_en')->label('Backend label (EN)'),
                        TextInput::make('technology_data_label_en')->label('Data label (EN)'),
                        TextInput::make('technology_integration_label_en')->label('Integration label (EN)'),
                        TextInput::make('technology_collaboration_label_en')->label('Collaboration label (EN)'),
                    ]),
                ]),

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
            EditorialActions::publish(fn (): SiteConfiguration => $this->record, $this->syncRecord(...)),
            EditorialActions::show(fn (): SiteConfiguration => $this->record, $this->syncRecord(...)),
            EditorialActions::hide(fn (): SiteConfiguration => $this->record, $this->syncRecord(...)),
            EditorialActions::returnToDraft(fn (): SiteConfiguration => $this->record, $this->syncRecord(...)),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        try {
            $updated = app(UpdateContent::class)($this->record, $state);
        } catch (PublicationValidationException $exception) {
            $this->notifyValidationFailure($exception);

            return;
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

    private function syncRecord(SiteConfiguration $updated): void
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
