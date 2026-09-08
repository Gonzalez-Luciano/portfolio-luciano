<?php

namespace App\Filament\Resources\Experiences\Tables;

use App\Domain\Content\Actions\ReorderContent;
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

class ExperiencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('position')
            ->columns([
                TextColumn::make('key')->label('Key'),
                TextColumn::make('role_es')->label('Role (ES)')->limit(40),
                TextColumn::make('role_en')->label('Role (EN)')->limit(40),
                TextColumn::make('start_year')->label('Start')->formatStateUsing(fn (Model $record): string => sprintf('%04d-%02d', $record->start_year, $record->start_month)),
                TextColumn::make('end_year')->label('End')->formatStateUsing(fn (Model $record): string => $record->end_year !== null ? sprintf('%04d-%02d', $record->end_year, $record->end_month) : 'Current'),
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
