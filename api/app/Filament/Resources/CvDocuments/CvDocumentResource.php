<?php

namespace App\Filament\Resources\CvDocuments;

use App\Filament\Resources\CvDocuments\Pages\CreateCvDocument;
use App\Filament\Resources\CvDocuments\Pages\EditCvDocument;
use App\Filament\Resources\CvDocuments\Pages\ListCvDocuments;
use App\Filament\Resources\CvDocuments\Schemas\CvDocumentForm;
use App\Filament\Resources\CvDocuments\Tables\CvDocumentsTable;
use App\Filament\Support\ReviewLink;
use App\Models\CvDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CvDocumentResource extends Resource
{
    protected static ?string $model = CvDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Profile and site';

    public static function form(Schema $schema): Schema
    {
        return CvDocumentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CvDocumentsTable::configure($table)->pushRecordActions([ReviewLink::rowAction()]);
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
            'index' => ListCvDocuments::route('/'),
            'create' => CreateCvDocument::route('/create'),
            'edit' => EditCvDocument::route('/{record}/edit'),
        ];
    }
}
