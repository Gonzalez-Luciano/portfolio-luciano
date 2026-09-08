<?php

namespace App\Filament\Resources\WorkPrinciples;

use App\Filament\Resources\WorkPrinciples\Pages\CreateWorkPrinciple;
use App\Filament\Resources\WorkPrinciples\Pages\EditWorkPrinciple;
use App\Filament\Resources\WorkPrinciples\Pages\ListWorkPrinciples;
use App\Filament\Resources\WorkPrinciples\Schemas\WorkPrincipleForm;
use App\Filament\Resources\WorkPrinciples\Tables\WorkPrinciplesTable;
use App\Models\WorkPrinciple;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class WorkPrincipleResource extends Resource
{
    protected static ?string $model = WorkPrinciple::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Profile and site';

    public static function form(Schema $schema): Schema
    {
        return WorkPrincipleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WorkPrinciplesTable::configure($table);
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
            'index' => ListWorkPrinciples::route('/'),
            'create' => CreateWorkPrinciple::route('/create'),
            'edit' => EditWorkPrinciple::route('/{record}/edit'),
        ];
    }
}
