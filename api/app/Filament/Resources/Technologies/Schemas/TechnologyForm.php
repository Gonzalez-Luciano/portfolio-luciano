<?php

namespace App\Filament\Resources\Technologies\Schemas;

use App\Enums\PublicationStatus;
use App\Enums\TechnologyCategory;
use App\Models\Technology;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class TechnologyForm
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

                TextInput::make('name')
                    ->label('Name')
                    ->maxLength(255)
                    ->helperText('Canonical technology name, used as-is in both locales. Required to publish. Not required to save a draft.'),

                Select::make('category')
                    ->label('Category')
                    // A closed, non-translated set: the enum's declared
                    // order (backend, data, integration, collaboration) is
                    // canonical and must be preserved here.
                    ->options(collect(TechnologyCategory::cases())->mapWithKeys(
                        fn (TechnologyCategory $case): array => [$case->value => $case->name],
                    )->all())
                    ->native(false)
                    ->required(),

                FileUpload::make('icon')
                    ->label('Icon')
                    ->storeFiles(false)
                    ->acceptedFileTypes(['image/png', 'image/webp'])
                    // Icons are decorative: no alt text field exists for
                    // them anywhere in this schema or the underlying model.
                    ->helperText('Optional. PNG or WebP, up to 1 MiB, decorative (no alt text). Uploading a new icon replaces the current one only after saving.'),
                Placeholder::make('icon_display')
                    ->label('Current icon')
                    ->content(fn (?Technology $record): string => filled($record?->icon_private_path) ? 'Present' : 'None'),

                Placeholder::make('relations_display')
                    ->label('Used by')
                    ->content(function (?Technology $record): string {
                        if ($record === null) {
                            return 'Save first to see linked content.';
                        }

                        $experiences = $record->experiences()->count();
                        $workCases = $record->workCases()->count();
                        $projects = $record->projects()->count();

                        if ($experiences + $workCases + $projects === 0) {
                            return 'Not linked to any experience, work case, or project.';
                        }

                        return "{$experiences} experience(s), {$workCases} work case(s), {$projects} project(s).";
                    })
                    ->helperText('Informational only. A technology linked to any content cannot be deleted until those relations are removed from the owning experience, work case, or project.'),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?Technology $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?Technology $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?Technology $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?Technology $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?Technology $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
