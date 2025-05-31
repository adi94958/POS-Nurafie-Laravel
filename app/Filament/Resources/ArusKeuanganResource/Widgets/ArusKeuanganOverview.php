<?php

namespace App\Filament\Resources\ArusKeuanganResource\Widgets;

use App\Models\ArusKeuangan;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class ArusKeuanganOverview extends BaseWidget
{
    protected static ?string $pollingInterval = null;

    public ?string $dateFrom = null;
    public ?string $dateUntil = null;
    public ?string $activeTab = null;

    protected function getStats(): array
    {
        $idPemilik = Filament::auth()->user()?->pemilik?->id_pemilik;

        $data = ArusKeuangan::getSaldoStat(
            idPemilik: $idPemilik,
            from: $this->dateFrom,
            until: $this->dateUntil,
            jenisPembayaran: $this->activeTab
        );

        $periodDescription = $this->getPeriodDescription();
        $tabDescription = $this->getTabDescription();

        return [
            Stat::make('Total Saldo', $this->formatCurrency($data['saldo']))
                ->description('Selisih Debit & Kredit' . ($periodDescription ? " ({$periodDescription})" : '') . ($tabDescription ? " - {$tabDescription}" : ''))
                ->color($data['saldo'] >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Total Pemasukan (Debit)', $this->formatCurrency($data['total_debit']))
                ->description(($periodDescription ?: 'Semua waktu') . ($tabDescription ? " - {$tabDescription}" : ''))
                ->color('primary')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Total Pengeluaran (Kredit)', $this->formatCurrency($data['total_kredit']))
                ->description(($periodDescription ?: 'Semua waktu') . ($tabDescription ? " - {$tabDescription}" : ''))
                ->color('danger')
                ->icon('heroicon-o-arrow-trending-down'),
        ];
    }

    #[On('filterChanged')]
    public function updateFilters(array $filters = []): void
    {
        $this->dateFrom = $filters['created_from'] ?? null;
        $this->dateUntil = $filters['created_until'] ?? null;

        $this->dispatch('$refresh');
    }

    #[On('tabChanged')]
    public function updateActiveTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->dispatch('$refresh');
    }

    #[On('filterReset')]
    public function handleFilterReset(array $filters = [], string $activeTab = 'Semua'): void
    {
        $this->dateFrom = $filters['created_from'] ?? null;
        $this->dateUntil = $filters['created_until'] ?? null;
        $this->activeTab = $activeTab;

        $this->dispatch('$refresh');
    }

    public function resetFilters(): void
    {
        $this->dateFrom = null;
        $this->dateUntil = null;
    }

    protected function getPeriodDescription(): ?string
    {
        if ($this->dateFrom && $this->dateUntil) {
            return Carbon::parse($this->dateFrom)->translatedFormat('d M Y') . ' - ' .
                Carbon::parse($this->dateUntil)->translatedFormat('d M Y');
        }

        if ($this->dateFrom) {
            return 'Sejak ' . Carbon::parse($this->dateFrom)->translatedFormat('d M Y');
        }

        if ($this->dateUntil) {
            return 'Sampai ' . Carbon::parse($this->dateUntil)->translatedFormat('d M Y');
        }

        return null;
    }

    private function formatCurrency(int $amount): string
    {
        return 'Rp. ' . number_format($amount, 0, ',', '.');
    }

    protected function getTabDescription(): ?string
    {
        if (!$this->activeTab || $this->activeTab === 'Semua') {
            return null;
        }

        return "Pembayaran {$this->activeTab}";
    }
}
