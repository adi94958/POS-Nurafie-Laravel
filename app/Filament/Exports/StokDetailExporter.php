<?php

namespace App\Filament\Exports;

use App\Models\Stok;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Str;

class StokDetailExporter extends Exporter
{
    protected static ?string $model = Stok::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('produk.nama_produk')
                ->label('Produk')
                ->formatStateUsing(fn($state) => Str::ucwords($state)),
            ExportColumn::make('created_at')
                ->label('Tanggal')
                ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->translatedFormat('d M Y, \\J\\a\\m H:i')),
            ExportColumn::make('jenis_stok')
                ->label('Jenis Stok'),
            ExportColumn::make('jumlah_stok')
                ->label('Jumlah Stok'),
            ExportColumn::make('jenis_transaksi')
                ->label('Jenis Transaksi'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor selesai! ' . number_format($export->successful_rows) . ' baris berhasil diekspor.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diekspor.';
        }

        return $body;
    }
}
