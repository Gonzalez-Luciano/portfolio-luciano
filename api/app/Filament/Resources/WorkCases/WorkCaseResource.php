<?php

namespace App\Filament\Resources\WorkCases;

use App\Filament\Resources\WorkCases\Pages\CreateWorkCase;
use App\Filament\Resources\WorkCases\Pages\EditWorkCase;
use App\Filament\Resources\WorkCases\Pages\ListWorkCases;
use App\Filament\Resources\WorkCases\Schemas\WorkCaseForm;
use App\Filament\Resources\WorkCases\Tables\WorkCasesTable;
use App\Models\WorkCase;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WorkCaseResource extends Resource
{
    protected static ?string $model = WorkCase::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Career';

    public static function form(Schema $schema): Schema
    {
        return WorkCaseForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkCasesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkCases::route('/'),
            'create' => CreateWorkCase::route('/create'),
            'edit' => EditWorkCase::route('/{record}/edit'),
        ];
    }
}
