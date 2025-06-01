<?php

namespace App\Filament\Resources\PembelianResource\Widgets;

use App\Filament\Resources\PembelianResource\Pages\ListPembelians;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class PembelianOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListPembelians::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalhargaPembelian = $query->sum('total_harga');
        $count = $query->count();
        $pengeluaran = $query->withSum('pembayaran', 'total_bayar')->get()->sum('pembayaran_sum_total_bayar');
        $kembalian = $query->get()->sum(fn($pembelian) => $pembelian->uang_kembalian);
        $utang = $query->get()->sum(fn($pembelian) => $pembelian->sisa_pembayaran);

        $createdFrom = $this->tableFilters['created_at']['created_from'] ?? null;
        $createdUntil = $this->tableFilters['created_at']['created_until'] ?? null;

        $description = $this->getPeriodDescriptionFromDates($createdFrom, $createdUntil);

        return [
            Stat::make('Total Pembelian', $count)
                ->description($description)
                ->color('secondary')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Total Harga Pembelian', $this->formatCurrency($totalhargaPembelian))
                ->description($description)
                ->color('success')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Total Pengeluaran', $this->formatCurrency($pengeluaran - $kembalian))
                ->description($description)
                ->color('primary')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Utang', $this->formatCurrency($utang))
                ->description($description)
                ->color('danger')
                ->icon('heroicon-o-exclamation-circle'),
        ];
    }

    private function getPeriodDescriptionFromDates(?string $from, ?string $to): string
    {
        if (!$from && !$to) {
            return 'All Time';
        }

        $fromText = $from ? Carbon::parse($from)->translatedFormat('d M Y') : null;
        $toText = $to ? Carbon::parse($to)->translatedFormat('d M Y') : null;

        if ($fromText && $toText) {
            return "{$fromText} - {$toText}";
        }

        if ($fromText) {
            return "Sejak {$fromText}";
        }

        if ($toText) {
            return "Sampai {$toText}";
        }

        return '';
    }

    private function formatCurrency(int $amount): string
    {
        return 'Rp. ' . number_format($amount, 0, ',', '.');
    }
}
