<?php

namespace App\Filament\Resources\Experiences\Schemas;

use App\Enums\PublicationStatus;
use App\Models\Experience;
use App\Models\Technology;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ExperienceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(100)
                    ->unique(ignoreRecord: true)
                    ->helperText('Lowercase slug (letters, digits, hyphens). Change later using the "Change key" action.')
                    // The key is the record's identity. It is chosen here at
                    // creation and afterward changes only through the
                    // dedicated "Change key" action, never a plain field
                    // edit, so it is disabled (and not dehydrated) on edit.
                    ->disabledOn('edit'),

                TextInput::make('start_year')
                    ->label('Start year')
                    ->numeric()
                    ->integer()
                    ->required(),
                TextInput::make('start_month')
                    ->label('Start month')
                    ->numeric()
                    ->integer()
                    ->required()
                    ->helperText('1-12. Checked when publishing.'),
                TextInput::make('end_year')
                    ->label('End year')
                    ->numeric()
                    ->integer()
                    ->helperText('Leave both end fields blank for a current/ongoing experience.'),
                TextInput::make('end_month')
                    ->label('End month')
                    ->numeric()
                    ->integer()
                    ->helperText('Must be present together with the end year, and not before the start date. Checked when publishing.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('role_es')->label('Role (ES)')->maxLength(255),
                        Textarea::make('summary_es')->label('Summary (ES)')->maxLength(10000),
                        TextInput::make('organization_label_es')->label('Organization label (ES)')->maxLength(255),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('role_en')->label('Role (EN)')->maxLength(255),
                        Textarea::make('summary_en')->label('Summary (EN)')->maxLength(10000),
                        TextInput::make('organization_label_en')->label('Organization label (EN)')->maxLength(255),
                    ]),
                ]),

                Repeater::make('highlights')
                    ->label('Highlights')
                    ->addActionLabel('Add highlight')
                    ->schema([
                        Textarea::make('content_es')->label('Content (ES)')->maxLength(10000),
                        Textarea::make('content_en')->label('Content (EN)')->maxLength(10000),
                    ])
                    ->defaultItems(0)
                    ->columns(1)
                    ->helperText('Highlights are edited only here, not on a separate page. Drag to reorder; the order sets each highlight\'s position. Both languages are required for a highlight when this experience is published.')
                    ->hidden(fn (?Experience $record): bool => $record === null)
                    ->dehydrated(fn (?Experience $record): bool => $record !== null),
                Placeholder::make('highlights_notice')
                    ->label('Highlights')
                    ->content('Save the experience first, then add highlights here.')
                    ->visible(fn (?Experience $record): bool => $record === null),

                Repeater::make('technologies')
                    ->label('Technologies')
                    ->addActionLabel('Add technology')
                    ->schema([
                        Select::make('technology_id')
                            ->label('Technology')
                            ->options(fn (): array => Technology::query()->orderBy('key')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->defaultItems(0)
                    ->columns(1)
                    ->helperText('Drag to reorder; the order sets each technology\'s position. The same technology cannot be related more than once.')
                    ->hidden(fn (?Experience $record): bool => $record === null)
                    ->dehydrated(fn (?Experience $record): bool => $record !== null),
                Placeholder::make('technologies_notice')
                    ->label('Technologies')
                    ->content('Save the experience first, then add technologies here.')
                    ->visible(fn (?Experience $record): bool => $record === null),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?Experience $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?Experience $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?Experience $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?Experience $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?Experience $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
