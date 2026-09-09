<?php

namespace App\Filament\Resources\ProfessionalLinks\Schemas;

use App\Enums\ProfessionalLinkType;
use App\Enums\PublicationStatus;
use App\Models\ProfessionalLink;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProfessionalLinkForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label('Type')
                    ->options(collect(ProfessionalLinkType::cases())->mapWithKeys(
                        fn (ProfessionalLinkType $case): array => [$case->value => $case->name],
                    )->all())
                    ->native(false)
                    ->required()
                    ->unique(ignoreRecord: true)
                    // The link type is the record's identity, chosen once at
                    // creation; it is never editable afterward through any
                    // dedicated action, so it is disabled (and therefore not
                    // dehydrated/submitted) on the edit form.
                    ->disabledOn('edit'),

                TextInput::make('destination')
                    ->label('Destination')
                    ->required()
                    ->maxLength(2048)
                    ->helperText('LinkedIn and GitHub require a valid HTTPS URL. Email must be a plain address without a "mailto:" prefix. Checked when publishing.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('label_es')->label('Label (ES)')->maxLength(255),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('label_en')->label('Label (EN)')->maxLength(255),
                    ]),
                ]),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?ProfessionalLink $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?ProfessionalLink $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?ProfessionalLink $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?ProfessionalLink $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
