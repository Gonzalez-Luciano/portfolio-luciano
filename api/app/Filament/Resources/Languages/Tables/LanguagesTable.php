<?php

namespace App\Filament\Resources\Languages\Tables;

use App\Domain\Content\Actions\ReorderContent;
use App\Enums\LanguageLevel;
use App\Enums\PublicationStatus;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LanguagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('key')->label('Key'),
                TextColumn::make('name_es')->label('Name (ES)'),
                TextColumn::make('name_en')->label('Name (EN)'),
                TextColumn::make('level')
                    ->label('Level')
                    ->formatStateUsing(fn (?LanguageLevel $state): string => $state === null ? '—' : strtoupper($state->value)),
                TextColumn::make('position')->label('Position')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (PublicationStatus $state): string => ucfirst($state->value))
                    ->color(fn (PublicationStatus $state): string => $state === PublicationStatus::Published ? 'success' : 'gray'),
                TextColumn::make('is_visible')
                    ->label('Visibility')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Visible' : 'Hidden')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    PublicationStatus::Draft->value => 'Draft',
                    PublicationStatus::Published->value => 'Published',
                ]),
                TernaryFilter::make('is_visible')
                    ->label('Visibility')
                    ->trueLabel('Visible')
                    ->falseLabel('Hidden'),
            ])
            ->recordActions([
                self::reorderAction(),
                EditAction::make(),
            ]);
    }

    private static function reorderAction(): Action
    {
        return Action::make('reorder')
            ->label('Change position')
            ->icon('heroicon-o-arrows-up-down')
            ->schema([
                TextInput::make('position')
                    ->label('New position')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->required()
                    ->default(fn (Model $record): int => $record->position),
            ])
            ->action(function (Model $record, array $data): void {
                try {
                    app(ReorderContent::class)($record, (int) $data['position']);
                } catch (\InvalidArgumentException $exception) {
                    Notification::make()->danger()->title('Reorder failed')->body($exception->getMessage())->send();

                    return;
                }

                Notification::make()->success()->title('Position updated')->send();
            });
    }
}
