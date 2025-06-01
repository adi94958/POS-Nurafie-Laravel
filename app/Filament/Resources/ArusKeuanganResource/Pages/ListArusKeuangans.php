<?php

namespace App\Filament\Resources\ArusKeuanganResource\Pages;

use App\Filament\Resources\ArusKeuanganResource;
use App\Filament\Resources\ArusKeuanganResource\Widgets\ArusKeuanganOverview;
use Filament\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListArusKeuangans extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = ArusKeuanganResource::class;
    protected static ?string $title = 'Daftar Arus Keuangan';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Arus Keuangan'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ArusKeuanganOverview::class,
        ];
    }

    public function getActiveTab(): string
    {
        return $this->activeTab ?? 'Semua';
    }

    public function getTableFilters(): array
    {
        return $this->tableFilters ?? [];
    }

    public function getTabs(): array
    {
        return [
            'Semua' => Tab::make('Semua')
                ->icon('heroicon-o-bars-3-bottom-left')
                ->modifyQueryUsing(fn(Builder $query) => $query->with('pembayaran')),

            'Tunai' => Tab::make('Tunai')
                ->icon('heroicon-o-banknotes')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->with('pembayaran')
                    ->whereHas('pembayaran', function ($q) {
                        $q->where('jenis_pembayaran', 'tunai');
                    })),

            'Transfer' => Tab::make('Transfer')
                ->icon('heroicon-o-credit-card')
                ->modifyQueryUsing(fn(Builder $query) => $query
                    ->with('pembayaran')
                    ->whereHas('pembayaran', function ($q) {
                        $q->where('jenis_pembayaran', 'transfer');
                    })),
        ];
    }

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        return $query->orderBy('created_at', 'asc')
            ->orderBy('id_arus_keuangan', 'asc');
    }
}


// namespace App\Filament\Resources\ArusKeuanganResource\Pages;

// use App\Filament\Resources\ArusKeuanganResource;
// use App\Filament\Resources\ArusKeuanganResource\Widgets\ArusKeuanganOverview;
// use Filament\Actions;
// use Filament\Resources\Components\Tab;
// use Filament\Resources\Pages\ListRecords;
// use Illuminate\Database\Eloquent\Builder;

// class ListArusKeuangans extends ListRecords
// {
//     protected static string $resource = ArusKeuanganResource::class;
//     protected static ?string $title = 'Daftar Arus Keuangan';

//     protected function getHeaderActions(): array
//     {
//         return [
//             Actions\CreateAction::make()->label('Buat Arus Keuangan'),
//         ];
//     }

//     public function getHeaderWidgets(): array
//     {
//         return [ArusKeuanganOverview::class];
//     }

//     public function getTabs(): array
//     {
//         return [
//             'Semua' => Tab::make('Semua')
//                 ->icon('heroicon-o-bars-3-bottom-left')
//                 ->modifyQueryUsing(fn(Builder $query) => $query->with('pembayaran')),

//             'Tunai' => Tab::make('Tunai')
//                 ->icon('heroicon-o-banknotes')
//                 ->modifyQueryUsing(fn(Builder $query) => $query
//                     ->with('pembayaran')
//                     ->whereHas('pembayaran', fn($q) => $q->where('jenis_pembayaran', 'tunai'))),

//             'Transfer' => Tab::make('Transfer')
//                 ->icon('heroicon-o-credit-card')
//                 ->modifyQueryUsing(fn(Builder $query) => $query
//                     ->with('pembayaran')
//                     ->whereHas('pembayaran', fn($q) => $q->where('jenis_pembayaran', 'transfer'))),
//         ];
//     }

//     public function mount(): void
//     {
//         parent::mount();
//         $this->sendFilterToWidget();
//         $this->sendTabToWidget();
//     }

//     public function updatedTableFilters(): void
//     {
//         parent::updatedTableFilters();
//         $this->sendFilterToWidget();
//     }

//     public function updatedActiveTab(): void
//     {
//         $this->sendTabToWidget();
//     }

//     public function resetTableFiltersForm(): void
//     {
//         parent::resetTableFiltersForm();

//         $this->dispatch('filterReset', [
//             'created_from' => null,
//             'created_until' => null,
//         ], $this->getActiveTab());
//     }

//     protected function getTableQuery(): Builder
//     {
//         return parent::getTableQuery()
//             ->orderBy('created_at', 'asc')
//             ->orderBy('id_arus_keuangan', 'asc');
//     }

//     protected function sendFilterToWidget(): void
//     {
//         $filters = $this->getTableFilters();
//         $filterData = [
//             'created_from' => $filters['created_at']['created_from'] ?? null,
//             'created_until' => $filters['created_at']['created_until'] ?? null,
//         ];

//         $this->dispatch('filterChanged', $filterData);
//     }

//     protected function sendTabToWidget(): void
//     {
//         $activeTab = $this->getActiveTab();
//         $this->dispatch('tabChanged', $activeTab);
//     }

//     public function getActiveTab(): string
//     {
//         return $this->activeTab ?? 'Semua';
//     }

//     public function getTableFilters(): array
//     {
//         return $this->tableFilters ?? [];
//     }

//     protected function applyFiltersToTableQuery(Builder $query): Builder
//     {
//         $filters = $this->getTableFilters();
//         $createdFrom = $filters['created_at']['created_from'] ?? null;
//         $createdUntil = $filters['created_at']['created_until'] ?? null;

//         $this->dispatch('filterChanged', [
//             'created_from' => $createdFrom,
//             'created_until' => $createdUntil,
//         ]);

//         return $query;
//     }
// }
