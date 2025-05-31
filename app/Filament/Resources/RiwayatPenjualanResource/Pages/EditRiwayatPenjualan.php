<?php

namespace App\Filament\Resources\RiwayatPenjualanResource\Pages;

use App\Filament\Resources\RiwayatPenjualanResource;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\Stok;
use App\Models\Produk;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;
use Exception;

class EditRiwayatPenjualan extends EditRecord
{
    protected static string $resource = RiwayatPenjualanResource::class;
    protected static ?string $title = 'Edit Transaksi Penjualan';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSessionKey(): string
    {
        return 'penjualan_original_data_' . $this->record->id_penjualan;
    }

    public function mount($record): void
    {
        parent::mount($record);
        $this->captureOriginalPenjualanDetailData($record);
    }

    protected function captureOriginalPenjualanDetailData($recordId): void
    {
        $penjualan = Penjualan::with('penjualanDetail')->find($recordId);

        if (!$penjualan) {
            return;
        }

        $originalData = [];

        foreach ($penjualan->penjualanDetail as $detail) {
            $originalData[$detail->id_penjualan_detail] = [
                'id_produk' => $detail->id_produk,
                'jumlah_produk' => (int) $detail->jumlah_produk,
            ];
        }

        Session::put($this->getSessionKey(), $originalData);
    }

    protected function getOriginalPenjualanDetailData(): array
    {
        return Session::get($this->getSessionKey(), []);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $stockValidationResults = $this->validateStockChanges($record, $data);

        if (!$stockValidationResults['valid']) {
            foreach ($stockValidationResults['errors'] as $error) {
                Notification::make()
                    ->title('Perubahan Stok Tidak Valid')
                    ->body($error)
                    ->danger()
                    ->persistent()
                    ->send();
            }

            $this->halt();
        }

        return DB::transaction(function () use ($record, $data, $stockValidationResults) {
            $total = collect($data['penjualanDetail'] ?? [])
                ->sum(fn($item) => $item['sub_total_harga'] ?? 0);

            $diskon = $data['diskon'] ?? 0;
            $totalSetelahDiskon = $total - $diskon;

            $pembayaranItems = collect($data['pembayaranPenjualan'] ?? []);
            $totalPembayaran = 0;

            foreach ($pembayaranItems as $item) {
                if (isset($item['id_pembayaran']) && $item['id_pembayaran']) {
                    $pembayaran = Pembayaran::find($item['id_pembayaran']);
                    if ($pembayaran) {
                        $pembayaran->update([
                            'total_bayar' => $item['total_bayar'],
                            'jenis_pembayaran' => $item['jenis_pembayaran'],
                            'id_tipe_transfer' => $item['id_tipe_transfer'] ?? null,
                            'keterangan' => $item['keterangan'] ?? null,
                        ]);
                    }
                }
                $totalPembayaran += $item['total_bayar'] ?? 0;
            }

            $statusPenjualan = $totalPembayaran >= $totalSetelahDiskon ? 'lunas' : 'belum lunas';
            if ($record->status_penjualan === 'pesanan') {
                $statusPenjualan = $record->status_penjualan;
            }

            $record->update([
                'id_pelanggan' => $data['id_pelanggan'],
                'id_kasir' => $data['id_kasir'],
                'total_harga' => $totalSetelahDiskon,
                'diskon' => $diskon,
                'status_penjualan' => $statusPenjualan,
                'uang_diterima' => $totalPembayaran,
                'uang_kembalian' => $totalPembayaran > $totalSetelahDiskon ? $totalPembayaran - $totalSetelahDiskon : 0,
                'sisa_pembayaran' => $totalPembayaran < $totalSetelahDiskon ? $totalSetelahDiskon - $totalPembayaran : 0,
            ]);

            $this->handleStockChanges($record, $data);

            foreach ($stockValidationResults['changes'] as $change) {
                if ($change['selisih'] != 0) {
                    Notification::make()
                        ->title('Stok Produk Berhasil Diubah')
                        ->body($change['message'])
                        ->success()
                        ->send();
                }
            }

            Session::forget($this->getSessionKey());

            return $record;
        });
    }

    protected function validateStockChanges(Model $record, array $data): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'changes' => [],
        ];

        if ($record->status_penjualan === 'pesanan') {
            return $result;
        }

        $originalPenjualanDetailData = $this->getOriginalPenjualanDetailData();
        $newPenjualanDetails = collect($data['penjualanDetail'] ?? []);

        foreach ($newPenjualanDetails as $newDetail) {
            if (!isset($newDetail['id_penjualan_detail'])) {
                continue;
            }

            $detailId = $newDetail['id_penjualan_detail'];
            $originalDetail = $originalPenjualanDetailData[$detailId] ?? null;

            if (!$originalDetail) {
                continue;
            }

            $originalJumlah = $originalDetail['jumlah_produk'];
            $newJumlah = (int) ($newDetail['jumlah_produk'] ?? 0);
            $idProduk = $originalDetail['id_produk'];
            $selisih = $newJumlah - $originalJumlah;

            $produk = Produk::find($idProduk);
            $namaProduk = $produk ? $produk->nama_produk : "Produk #$idProduk";

            if ($selisih == 0) {
                continue;
            }

            // Untuk penjualan, logika berbeda:
            // - Jika selisih > 0 (jumlah naik), stok keluar bertambah
            // - Jika selisih < 0 (jumlah turun), stok masuk bertambah (return)
            $result['changes'][] = [
                'id_produk' => $idProduk,
                'nama_produk' => $namaProduk,
                'original_jumlah' => $originalJumlah,
                'new_jumlah' => $newJumlah,
                'selisih' => $selisih,
                'message' => $selisih > 0
                    ? "Stok $namaProduk berkurang karena penjualan bertambah dari $originalJumlah menjadi $newJumlah (stok out +$selisih)"
                    : "Stok $namaProduk bertambah karena penjualan berkurang dari $originalJumlah menjadi $newJumlah (stok in +" . abs($selisih) . ")"
            ];

            // Validasi stok tersedia jika penjualan bertambah (stok keluar)
            if ($selisih > 0) {
                $stokMasuk = Stok::where('id_produk', $idProduk)
                    ->where('jenis_stok', 'In')
                    ->sum('jumlah_stok');

                $stokKeluar = Stok::where('id_produk', $idProduk)
                    ->where('jenis_stok', 'Out')
                    ->sum('jumlah_stok');

                $stokTersedia = $stokMasuk - $stokKeluar;

                $penambahan = $selisih; // Penambahan penjualan = penambahan stok keluar

                if ($penambahan > $stokTersedia) {
                    $result['valid'] = false;
                    $result['errors'][] = "Perubahan penjualan $namaProduk tidak dapat dilakukan. Stok tersedia hanya $stokTersedia, sedangkan Anda mencoba menambah penjualan sebanyak $penambahan.";
                }
            }
        }

        return $result;
    }

    protected function handleStockChanges(Model $record, array $data): void
    {
        if ($record->status_penjualan === 'pesanan') {
            return;
        }

        $originalPenjualanDetailData = $this->getOriginalPenjualanDetailData();
        $newPenjualanDetails = collect($data['penjualanDetail'] ?? []);

        foreach ($newPenjualanDetails as $newDetail) {
            if (!isset($newDetail['id_penjualan_detail'])) {
                // Handle new detail items
                if (isset($newDetail['id_produk']) && isset($newDetail['jumlah_produk']) && (int)$newDetail['jumlah_produk'] > 0) {
                    Stok::create([
                        'id_produk' => $newDetail['id_produk'],
                        'jumlah_stok' => (int)$newDetail['jumlah_produk'],
                        'jenis_stok' => 'Out',
                        'jenis_transaksi' => 'Penjualan',
                        'keterangan' => 'Stok keluar dari penjualan baru #' . $record->id_penjualan,
                    ]);
                }
                continue;
            }

            $detailId = $newDetail['id_penjualan_detail'];
            $originalDetail = $originalPenjualanDetailData[$detailId] ?? null;

            if (!$originalDetail) {
                continue;
            }

            $originalJumlah = $originalDetail['jumlah_produk'];
            $newJumlah = (int) ($newDetail['jumlah_produk'] ?? 0);
            $idProduk = $originalDetail['id_produk'];
            $selisih = $newJumlah - $originalJumlah;

            if ($selisih == 0) {
                continue;
            }

            // Logika untuk penjualan:
            // - Jika selisih > 0 (penjualan bertambah), maka stok keluar bertambah
            // - Jika selisih < 0 (penjualan berkurang), maka stok masuk bertambah (return)
            if ($selisih > 0) {
                // Penjualan bertambah = stok keluar bertambah
                Stok::create([
                    'id_produk' => $idProduk,
                    'jumlah_stok' => $selisih,
                    'jenis_stok' => 'Out',
                    'jenis_transaksi' => 'Penjualan',
                    'keterangan' => 'Stok keluar dari penambahan penjualan #' . $record->id_penjualan,
                ]);
            } else {
                // Penjualan berkurang = stok masuk bertambah (return)
                Stok::create([
                    'id_produk' => $idProduk,
                    'jumlah_stok' => abs($selisih),
                    'jenis_stok' => 'In',
                    'jenis_transaksi' => 'Penjualan',
                    'keterangan' => 'Stok masuk dari pengurangan penjualan (return) #' . $record->id_penjualan,
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $penjualan = Penjualan::with(['penjualanDetail', 'pembayaranPenjualan.pembayaran'])
            ->find($this->record->id_penjualan);

        if (!$penjualan) {
            return $data;
        }

        foreach ($penjualan->pembayaranPenjualan as $index => $pembayaranRelation) {
            $pembayaran = $pembayaranRelation->pembayaran;
            if ($pembayaran) {
                $data['pembayaranPenjualan'][$index]['total_bayar'] = $pembayaran->total_bayar;
                $data['pembayaranPenjualan'][$index]['jenis_pembayaran'] = $pembayaran->jenis_pembayaran;
                $data['pembayaranPenjualan'][$index]['id_tipe_transfer'] = $pembayaran->id_tipe_transfer;
                $data['pembayaranPenjualan'][$index]['keterangan'] = $pembayaran->keterangan;

                if ($pembayaran->id_tipe_transfer) {
                    $tipeTransfer = \App\Models\TipeTransfer::find($pembayaran->id_tipe_transfer);
                    if ($tipeTransfer) {
                        $data['pembayaranPenjualan'][$index]['tipe_pembayaran'] = $tipeTransfer->metode_transfer;
                    }
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $penjualanDetail = $data['penjualanDetail'] ?? [];
        $totalHarga = 0;

        foreach ($penjualanDetail as $item) {
            $totalHarga += $item['sub_total_harga'] ?? 0;
        }

        $diskon = $data['diskon'] ?? 0;
        $totalSetelahDiskon = $totalHarga - $diskon;

        $pembayaranItems = $data['pembayaranPenjualan'] ?? [];
        $totalPembayaran = 0;

        foreach ($pembayaranItems as $item) {
            $totalPembayaran += $item['total_bayar'] ?? 0;
        }

        $data['total_harga'] = $totalSetelahDiskon;
        $data['status_penjualan'] = $totalPembayaran >= $totalSetelahDiskon ? 'lunas' : 'belum lunas';
        $data['uang_diterima'] = $totalPembayaran;
        $data['uang_kembalian'] = $totalPembayaran > $totalSetelahDiskon ? $totalPembayaran - $totalSetelahDiskon : 0;
        $data['sisa_pembayaran'] = $totalPembayaran < $totalSetelahDiskon ? $totalSetelahDiskon - $totalPembayaran : 0;

        return $data;
    }
}
