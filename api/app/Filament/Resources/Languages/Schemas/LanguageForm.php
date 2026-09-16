<?php

namespace App\Filament\Resources\Languages\Schemas;

use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use App\Models\Language;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class LanguageForm
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

                Select::make('level')
                    ->label('Level')
                    ->options([
                        LanguageLevel::Native->value => 'Native',
                        LanguageLevel::A1->value => 'A1',
                        LanguageLevel::A2->value => 'A2',
                        LanguageLevel::B1->value => 'B1',
                        LanguageLevel::B2->value => 'B2',
                        LanguageLevel::C1->value => 'C1',
                        LanguageLevel::C2->value => 'C2',
                    ])
                    ->helperText('Required to publish.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('name_es')->label('Name (ES)')->maxLength(255),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('name_en')->label('Name (EN)')->maxLength(255),
                    ]),
                ]),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?Language $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?Language $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?Language $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?Language $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?Language $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
