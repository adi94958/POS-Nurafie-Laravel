<?php

namespace App\Filament\Exports;

use App\Models\Pembelian;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class PembelianExporter extends Exporter
{
    protected static ?string $model = Pembelian::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('pembelian.id_pembelian')
                ->label('No Invoice'),
            ExportColumn::make('pemasok.nama_perusahaan')
                ->label('Nama Perusahaan Pemasok'),
            ExportColumn::make('total_harga')
                ->label('Total Harga')
                ->formatStateUsing(fn($state) => 'Rp. ' . number_format($state, 0, ',', '.')),
            ExportColumn::make('pembelian.created_at')
                ->label('Tanggal Pembelian')
                ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->translatedFormat('d M Y, \\J\\a\\m H:i')),
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
