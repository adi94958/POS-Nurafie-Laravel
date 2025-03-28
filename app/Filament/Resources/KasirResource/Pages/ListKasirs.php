<?php

namespace App\Filament\Resources\KasirResource\Pages;

use App\Filament\Resources\KasirResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListKasirs extends ListRecords
{
    protected static string $resource = KasirResource::class;
    protected static ?string $title = 'Daftar Kasir';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Kasir'),
        ];
    }
}
