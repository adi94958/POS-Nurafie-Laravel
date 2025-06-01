<?php

namespace App\Filament\Resources\ArusKeuanganResource\Widgets;

use Carbon\Carbon;
use App\Filament\Resources\ArusKeuanganResource\Pages\ListArusKeuangans;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArusKeuanganOverview extends BaseWidget
{
    use InteractsWithPageTable;

    protected function getTablePage(): string
    {
        return ListArusKeuangans::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $kredit = $query->get()->sum(fn($item) => (int) $item->nominal_kredit);
        $debit = $query->get()->sum(fn($item) => (int) $item->nominal_debit);
        $omzet = $debit - $kredit;

        $createdFrom = $this->tableFilters['created_at']['created_from'] ?? null;
        $createdUntil = $this->tableFilters['created_at']['created_until'] ?? null;

        $description = $this->getPeriodDescriptionFromDates($createdFrom, $createdUntil);

        return [
            Stat::make('Total Saldo', $this->formatCurrency($omzet))
                ->description($description)
                ->color($omzet >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Pemasukan (Debit)', $this->formatCurrency($debit))
                ->description($description)
                ->color('primary')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Total Pengeluaran (Kredit)', $this->formatCurrency($kredit))
                ->description($description)
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),
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
