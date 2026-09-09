<?php

namespace App\Filament\Resources\ProfessionalLinks;

use App\Filament\Resources\ProfessionalLinks\Pages\CreateProfessionalLink;
use App\Filament\Resources\ProfessionalLinks\Pages\EditProfessionalLink;
use App\Filament\Resources\ProfessionalLinks\Pages\ListProfessionalLinks;
use App\Filament\Resources\ProfessionalLinks\Schemas\ProfessionalLinkForm;
use App\Filament\Resources\ProfessionalLinks\Tables\ProfessionalLinksTable;
use App\Filament\Support\ReviewLink;
use App\Models\ProfessionalLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProfessionalLinkResource extends Resource
{
    protected static ?string $model = ProfessionalLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Profile and site';

    public static function form(Schema $schema): Schema
    {
        return ProfessionalLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProfessionalLinksTable::configure($table)->pushRecordActions([ReviewLink::rowAction()]);
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
            'index' => ListProfessionalLinks::route('/'),
            'create' => CreateProfessionalLink::route('/create'),
            'edit' => EditProfessionalLink::route('/{record}/edit'),
        ];
    }
}
