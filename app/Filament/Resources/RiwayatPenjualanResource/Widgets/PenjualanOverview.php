<?php

namespace App\Filament\Resources\RiwayatPenjualanResource\Widgets;

use App\Filament\Resources\RiwayatPenjualanResource\Pages\ListRiwayatPenjualans;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class PenjualanOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListRiwayatPenjualans::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $omset = $query->sum('total_harga');
        $count = $query->count();
        $pendapatan = $query->withSum('pembayaran', 'total_bayar')->get()->sum('pembayaran_sum_total_bayar');
        $kembalian = $query->get()->sum(function ($penjualan) {
            return $penjualan->uang_kembalian;
        });
        $piutang = $query->get()->sum(function ($penjualan) {
            return $penjualan->sisa_pembayaran;
        });

        $createdFrom = $this->tableFilters['created_at']['created_from'] ?? null;
        $createdUntil = $this->tableFilters['created_at']['created_until'] ?? null;

        $description = $this->getPeriodDescriptionFromDates($createdFrom, $createdUntil);

        return [
            Stat::make('Total Penjualan', $count)
                ->description($description)
                ->color('secondary')
                ->icon('heroicon-o-shopping-cart'),

            Stat::make('Total Omset', $this->formatCurrency($omset))
                ->description($description)
                ->color('success')
                ->icon('heroicon-o-chart-bar'),

            Stat::make('Total Pendapatan', $this->formatCurrency($pendapatan - $kembalian))
                ->description($description)
                ->color('primary')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Piutang', $this->formatCurrency($piutang))
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
