<?php

namespace App\Filament\Resources\EducationEntries;

use App\Filament\Resources\EducationEntries\Pages\CreateEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\EditEducationEntry;
use App\Filament\Resources\EducationEntries\Pages\ListEducationEntries;
use App\Filament\Resources\EducationEntries\Schemas\EducationEntryForm;
use App\Filament\Resources\EducationEntries\Tables\EducationEntriesTable;
use App\Filament\Support\ReviewLink;
use App\Models\EducationEntry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class EducationEntryResource extends Resource
{
    protected static ?string $model = EducationEntry::class;

    protected static ?string $navigationLabel = 'Education';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Profile and site';

    public static function form(Schema $schema): Schema
    {
        return EducationEntryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EducationEntriesTable::configure($table)->pushRecordActions([ReviewLink::rowAction()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEducationEntries::route('/'),
            'create' => CreateEducationEntry::route('/create'),
            'edit' => EditEducationEntry::route('/{record}/edit'),
        ];
    }
}
