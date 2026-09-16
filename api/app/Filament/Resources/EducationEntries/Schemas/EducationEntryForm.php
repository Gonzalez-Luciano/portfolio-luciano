<?php

namespace App\Filament\Resources\EducationEntries\Schemas;

use App\Enums\PublicationStatus;
use App\Models\EducationEntry;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class EducationEntryForm
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
                    ->disabledOn('edit'),

                TextInput::make('institution')
                    ->label('Institution')
                    ->maxLength(255)
                    ->helperText('Required to publish. Not translated.'),
                TextInput::make('start_year')
                    ->label('Start year')
                    ->numeric()
                    ->integer()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->helperText('Optional.'),
                TextInput::make('end_year')
                    ->label('End year')
                    ->numeric()
                    ->integer()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->helperText('Optional. Must not precede the start year.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('program_es')->label('Program (ES)')->maxLength(255),
                        TextInput::make('detail_es')->label('Detail (ES)')->maxLength(255)
                            ->helperText('Optional. Spanish and English must be filled together.'),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('program_en')->label('Program (EN)')->maxLength(255),
                        TextInput::make('detail_en')->label('Detail (EN)')->maxLength(255),
                    ]),
                ]),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?EducationEntry $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?EducationEntry $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?EducationEntry $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?EducationEntry $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?EducationEntry $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
