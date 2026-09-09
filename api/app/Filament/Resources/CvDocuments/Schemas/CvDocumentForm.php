<?php

namespace App\Filament\Resources\CvDocuments\Schemas;

use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use App\Models\CvDocument;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CvDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')
                    ->label('Locale')
                    // Only locales that do not already have a row (or the
                    // record's own current locale, while editing) are
                    // offered: at most one CV slot exists per locale.
                    ->options(function (?CvDocument $record): array {
                        return collect(SupportedLocale::cases())
                            ->filter(function (SupportedLocale $case) use ($record): bool {
                                if ($record !== null && $record->locale === $case) {
                                    return true;
                                }

                                return ! CvDocument::query()->where('locale', $case->value)->exists();
                            })
                            ->mapWithKeys(fn (SupportedLocale $case): array => [$case->value => $case->name])
                            ->all();
                    })
                    ->native(false)
                    ->required()
                    ->unique(ignoreRecord: true)
                    // The locale is the record's identity, chosen once at
                    // creation; `UpdateContent` rejects any attempt to
                    // change it afterward, so it is disabled (and
                    // therefore not dehydrated/submitted) on edit.
                    ->disabledOn('edit'),

                TextInput::make('label')
                    ->label('Label')
                    ->maxLength(255)
                    ->helperText('Locale-specific CV title. Required to publish. Not required to save a draft.'),

                FileUpload::make('pdf')
                    ->label('CV PDF')
                    ->storeFiles(false)
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5 * 1024)
                    // This asset is private-only: there is no public copy
                    // and no public-path field anywhere on this model.
                    ->helperText('Optional here; required to publish. Private only, never exposed as a public copy. Up to 5 MiB. Uploading a new PDF replaces the current one only after saving.'),
                Placeholder::make('pdf_display')
                    ->label('Current PDF')
                    ->content(fn (?CvDocument $record): string => filled($record?->private_path) ? 'Present' : 'None'),

                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?CvDocument $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?CvDocument $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?CvDocument $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }
}
