<?php

namespace App\Filament\Resources\WorkPrinciples\Pages;

use App\Filament\Resources\WorkPrinciples\WorkPrincipleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkPrinciples extends ListRecords
{
    protected static string $resource = WorkPrincipleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
