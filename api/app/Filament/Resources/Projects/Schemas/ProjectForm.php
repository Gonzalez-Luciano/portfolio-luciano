<?php

namespace App\Filament\Resources\Projects\Schemas;

use App\Enums\ProjectDeliveryStatus;
use App\Enums\ProjectKind;
use App\Enums\PublicationStatus;
use App\Models\Project;
use App\Models\Technology;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ProjectForm
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

                Select::make('kind')
                    ->label('Kind')
                    ->options([
                        ProjectKind::Client->value => 'Client project',
                        ProjectKind::Personal->value => 'Personal project',
                    ])
                    ->default(ProjectKind::Personal->value)
                    ->required()
                    ->live(),
                TextInput::make('client_name')
                    ->label('Client name')
                    ->maxLength(255)
                    ->visible(fn (Get $get): bool => self::isClient($get('kind')))
                    ->helperText('Required to publish a client project. Personal projects never store a client.'),
                Select::make('delivery_status')
                    ->label('Delivery status')
                    ->options([
                        ProjectDeliveryStatus::InProduction->value => 'In production',
                        ProjectDeliveryStatus::InUse->value => 'In use',
                        ProjectDeliveryStatus::PublicDemo->value => 'Public demo',
                        ProjectDeliveryStatus::InDevelopment->value => 'In development',
                    ])
                    ->helperText('Required to publish.'),

                Toggle::make('featured')
                    ->label('Featured'),

                TextInput::make('demo_url')
                    ->label('Demo URL')
                    ->helperText('Optional. Must be a valid HTTPS URL when present. Checked when publishing.'),
                TextInput::make('repository_url')
                    ->label('Repository URL')
                    ->helperText('Optional. Must be a valid HTTPS URL when present. Checked when publishing.'),

                Tabs::make('locales')->tabs([
                    Tab::make('Spanish')->schema([
                        TextInput::make('title_es')->label('Title (ES)')->maxLength(255),
                        TextInput::make('role_es')->label('Role (ES)')->maxLength(255),
                        Textarea::make('summary_es')->label('Summary (ES)')->maxLength(10000),
                        Textarea::make('problem_es')->label('Problem (ES)')->maxLength(10000),
                        Textarea::make('solution_es')->label('Solution (ES)')->maxLength(10000),
                        Textarea::make('result_es')->label('Result (ES)')->maxLength(10000),
                        TextInput::make('image_alt_es')->label('Image alt text (ES)')->maxLength(500),
                    ]),
                    Tab::make('English')->schema([
                        TextInput::make('title_en')->label('Title (EN)')->maxLength(255),
                        TextInput::make('role_en')->label('Role (EN)')->maxLength(255),
                        Textarea::make('summary_en')->label('Summary (EN)')->maxLength(10000),
                        Textarea::make('problem_en')->label('Problem (EN)')->maxLength(10000),
                        Textarea::make('solution_en')->label('Solution (EN)')->maxLength(10000),
                        Textarea::make('result_en')->label('Result (EN)')->maxLength(10000),
                        TextInput::make('image_alt_en')->label('Image alt text (EN)')->maxLength(500),
                    ]),
                ]),

                FileUpload::make('image')
                    ->label('Image')
                    ->image()
                    ->storeFiles(false)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(8 * 1024)
                    ->helperText('Optional. JPEG, PNG, or WebP, up to 8 MiB. Uploading a new image replaces the current one only after saving. Alt text in both languages is required for publication once an image is present.'),
                Placeholder::make('image_display')
                    ->label('Current image')
                    ->content(fn (?Project $record): string => filled($record?->image_private_path) ? 'Present' : 'None'),

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
                    ->hidden(fn (?Project $record): bool => $record === null)
                    ->dehydrated(fn (?Project $record): bool => $record !== null),
                Placeholder::make('technologies_notice')
                    ->label('Technologies')
                    ->content('Save the project first, then add technologies here.')
                    ->visible(fn (?Project $record): bool => $record === null),

                Placeholder::make('position_display')
                    ->label('Position')
                    ->content(fn (?Project $record): string => $record !== null ? (string) $record->position : 'Assigned on creation')
                    ->helperText('Use the "Change position" table action to reorder.'),
                Placeholder::make('key_locked_display')
                    ->label('Key locked')
                    ->content(fn (?Project $record): string => ($record?->key_locked ?? false) ? 'Yes' : 'No'),
                Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn (?Project $record): string => ucfirst($record?->status->value ?? PublicationStatus::Draft->value)),
                Placeholder::make('visibility_display')
                    ->label('Visibility')
                    ->content(fn (?Project $record): string => ($record?->is_visible ?? false) ? 'Visible' : 'Hidden'),
                Placeholder::make('published_at_display')
                    ->label('Published at')
                    ->content(fn (?Project $record): string => $record?->published_at?->toDateTimeString() ?? 'Never'),
            ]);
    }

    public static function isClient(mixed $kind): bool
    {
        return $kind === ProjectKind::Client || $kind === ProjectKind::Client->value;
    }
}
