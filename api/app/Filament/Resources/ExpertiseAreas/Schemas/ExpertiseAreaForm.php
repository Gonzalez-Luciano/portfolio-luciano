<?php

namespace App\Filament\Resources\ExpertiseAreas\Schemas;

use App\Enums\PublicationStatus;
use App\Models\ExpertiseArea;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ExpertiseAreaForm
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
                        Textarea::make('description_es')->label('Description (ES)')->maxLength(10000),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('title_en')->label('Title (EN)')->maxLength(255),
                        Textarea::make('description_en')->label('Description (EN)')->maxLength(10000),
                    ]),
                ]),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?ExpertiseArea $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?ExpertiseArea $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?ExpertiseArea $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?ExpertiseArea $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?ExpertiseArea $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
