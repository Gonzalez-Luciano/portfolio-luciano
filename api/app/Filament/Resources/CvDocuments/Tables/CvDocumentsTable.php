<?php

namespace App\Filament\Resources\CvDocuments\Tables;

use App\Enums\PublicationStatus;
use App\Enums\SupportedLocale;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CvDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('locale')
            ->columns([
                TextColumn::make('locale')
                    ->label('Locale')
                    ->badge()
                    ->formatStateUsing(fn (SupportedLocale $state): string => strtoupper($state->value)),
                TextColumn::make('label')->label('Label')->limit(40),
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
                EditAction::make(),
            ]);
    }
}
