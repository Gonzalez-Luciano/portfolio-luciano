<?php

namespace App\Filament\Resources\WorkCases\Schemas;

use App\Enums\PublicationStatus;
use App\Models\Technology;
use App\Models\WorkCase;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class WorkCaseForm
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

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('title_es')->label('Title (ES)')->maxLength(255),
                        Textarea::make('context_es')->label('Context (ES)')->maxLength(10000),
                        Textarea::make('problem_es')->label('Problem (ES)')->maxLength(10000),
                        Textarea::make('contribution_es')->label('Contribution (ES)')->maxLength(10000),
                        Textarea::make('technical_approach_es')->label('Technical approach (ES)')->maxLength(10000),
                        Textarea::make('outcome_es')->label('Outcome (ES)')->maxLength(10000),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('title_en')->label('Title (EN)')->maxLength(255),
                        Textarea::make('context_en')->label('Context (EN)')->maxLength(10000),
                        Textarea::make('problem_en')->label('Problem (EN)')->maxLength(10000),
                        Textarea::make('contribution_en')->label('Contribution (EN)')->maxLength(10000),
                        Textarea::make('technical_approach_en')->label('Technical approach (EN)')->maxLength(10000),
                        Textarea::make('outcome_en')->label('Outcome (EN)')->maxLength(10000),
                    ]),
                ]),

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
                    ->hidden(fn (?WorkCase $record): bool => $record === null)
                    ->dehydrated(fn (?WorkCase $record): bool => $record !== null),
                Placeholder::make('technologies_notice')
                    ->label('Technologies')
                    ->content('Save the work case first, then add technologies here.')
                    ->visible(fn (?WorkCase $record): bool => $record === null),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?WorkCase $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?WorkCase $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?WorkCase $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?WorkCase $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?WorkCase $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
