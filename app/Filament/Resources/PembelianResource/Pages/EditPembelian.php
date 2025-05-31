<?php

namespace App\Filament\Resources\PembelianResource\Pages;

use App\Filament\Resources\PembelianResource;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Stok;
use App\Models\Produk;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;
use Exception;

class EditPembelian extends EditRecord
{
    protected static string $resource = PembelianResource::class;

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
        return 'pembelian_original_data_' . $this->record->id_pembelian;
    }

    public function mount($record): void
    {
        parent::mount($record);
        $this->captureOriginalPembelianDetailData($record);
    }

    protected function captureOriginalPembelianDetailData($recordId): void
    {
        $pembelian = Pembelian::with('pembelianDetail')->find($recordId);

        if (!$pembelian) {
            return;
        }

        $originalData = [];

        foreach ($pembelian->pembelianDetail as $detail) {
            $originalData[$detail->id_pembelian_detail] = [
                'id_produk' => $detail->id_produk,
                'jumlah_produk' => (int) $detail->jumlah_produk,
            ];
        }

        Session::put($this->getSessionKey(), $originalData);
    }

    protected function getOriginalPembelianDetailData(): array
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
            $total = collect($data['pembelianDetail'] ?? [])
                ->sum(fn($item) => $item['sub_total_harga'] ?? 0);

            $pembayaranItems = collect($data['pembayaranPembelian'] ?? []);
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

            $statusPembelian = $totalPembayaran >= $total ? 'lunas' : 'belum lunas';
            if ($record->status_pembelian === 'diproses') {
                $statusPembelian = $record->status_pembelian;
            }

            $record->update([
                'id_pemasok' => $data['id_pemasok'],
                'total_harga' => $total,
                'status_pembelian' => $statusPembelian,
                'uang_diterima' => $totalPembayaran,
                'uang_kembalian' => $totalPembayaran > $total ? $totalPembayaran - $total : 0,
                'sisa_pembayaran' => $totalPembayaran < $total ? $total - $totalPembayaran : 0,
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

        if ($record->status_pembelian === 'diproses') {
            return $result;
        }

        $originalPembelianDetailData = $this->getOriginalPembelianDetailData();
        $newPembelianDetails = collect($data['pembelianDetail'] ?? []);

        foreach ($newPembelianDetails as $newDetail) {
            if (!isset($newDetail['id_pembelian_detail'])) {
                continue;
            }

            $detailId = $newDetail['id_pembelian_detail'];
            $originalDetail = $originalPembelianDetailData[$detailId] ?? null;

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

            $result['changes'][] = [
                'id_produk' => $idProduk,
                'nama_produk' => $namaProduk,
                'original_jumlah' => $originalJumlah,
                'new_jumlah' => $newJumlah,
                'selisih' => $selisih,
                'message' => $selisih > 0
                    ? "Stok $namaProduk bertambah dari $originalJumlah menjadi $newJumlah (+$selisih)"
                    : "Stok $namaProduk berkurang dari $originalJumlah menjadi $newJumlah (-" . abs($selisih) . ")"
            ];

            if ($selisih < 0) {
                $stokMasuk = Stok::where('id_produk', $idProduk)
                    ->where('jenis_stok', 'In')
                    ->sum('jumlah_stok');

                $stokKeluar = Stok::where('id_produk', $idProduk)
                    ->where('jenis_stok', 'Out')
                    ->sum('jumlah_stok');

                $stokTersedia = $stokMasuk - $stokKeluar;

                $pengurangan = abs($selisih);

                if ($pengurangan > $stokTersedia) {
                    $result['valid'] = false;
                    $result['errors'][] = "Perubahan stok $namaProduk tidak dapat dilakukan. Stok tersedia hanya $stokTersedia, sedangkan Anda mencoba mengurangi sebanyak $pengurangan.";
                }
            }
        }

        return $result;
    }

    protected function handleStockChanges(Model $record, array $data): void
    {
        if ($record->status_pembelian === 'diproses') {
            return;
        }

        $originalPembelianDetailData = $this->getOriginalPembelianDetailData();
        $newPembelianDetails = collect($data['pembelianDetail'] ?? []);

        foreach ($newPembelianDetails as $newDetail) {
            if (!isset($newDetail['id_pembelian_detail'])) {
                if (isset($newDetail['id_produk']) && isset($newDetail['jumlah_produk']) && (int)$newDetail['jumlah_produk'] > 0) {
                    Stok::create([
                        'id_produk' => $newDetail['id_produk'],
                        'jumlah_stok' => (int)$newDetail['jumlah_produk'],
                        'jenis_stok' => 'In',
                        'jenis_transaksi' => 'Pembelian',
                        'keterangan' => 'Stok masuk dari pembelian baru #' . $record->id_pembelian,
                    ]);
                }
                continue;
            }

            $detailId = $newDetail['id_pembelian_detail'];
            $originalDetail = $originalPembelianDetailData[$detailId] ?? null;

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

            if ($selisih < 0) {
                Stok::create([
                    'id_produk' => $idProduk,
                    'jumlah_stok' => abs($selisih),
                    'jenis_stok' => 'Out',
                    'jenis_transaksi' => 'Pembelian',
                    'keterangan' => 'Pengurangan stok dari edit pembelian #' . $record->id_pembelian,
                ]);
            } else {
                Stok::create([
                    'id_produk' => $idProduk,
                    'jumlah_stok' => $selisih,
                    'jenis_stok' => 'In',
                    'jenis_transaksi' => 'Pembelian',
                    'keterangan' => 'Penambahan stok dari edit pembelian #' . $record->id_pembelian,
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $pembelian = Pembelian::with(['pembelianDetail', 'pembayaranPembelian.pembayaran'])
            ->find($this->record->id_pembelian);

        if (!$pembelian) {
            return $data;
        }

        foreach ($pembelian->pembayaranPembelian as $index => $pembayaranRelation) {
            $pembayaran = $pembayaranRelation->pembayaran;
            if ($pembayaran) {
                $data['pembayaranPembelian'][$index]['total_bayar'] = $pembayaran->total_bayar;
                $data['pembayaranPembelian'][$index]['jenis_pembayaran'] = $pembayaran->jenis_pembayaran;
                $data['pembayaranPembelian'][$index]['id_tipe_transfer'] = $pembayaran->id_tipe_transfer;
                $data['pembayaranPembelian'][$index]['keterangan'] = $pembayaran->keterangan;

                if ($pembayaran->id_tipe_transfer) {
                    $tipeTransfer = \App\Models\TipeTransfer::find($pembayaran->id_tipe_transfer);
                    if ($tipeTransfer) {
                        $data['pembayaranPembelian'][$index]['tipe_pembayaran'] = $tipeTransfer->metode_transfer;
                    }
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $pembelianDetail = $data['pembelianDetail'] ?? [];
        $totalHarga = 0;

        foreach ($pembelianDetail as $item) {
            $totalHarga += $item['sub_total_harga'] ?? 0;
        }

        $pembayaranItems = $data['pembayaranPembelian'] ?? [];
        $totalPembayaran = 0;

        foreach ($pembayaranItems as $item) {
            $totalPembayaran += $item['total_bayar'] ?? 0;
        }

        $data['total_harga'] = $totalHarga;
        $data['status_pembelian'] = $totalPembayaran >= $totalHarga ? 'lunas' : 'belum lunas';
        $data['uang_diterima'] = $totalPembayaran;
        $data['uang_kembalian'] = $totalPembayaran > $totalHarga ? $totalPembayaran - $totalHarga : 0;
        $data['sisa_pembayaran'] = $totalPembayaran < $totalHarga ? $totalHarga - $totalPembayaran : 0;

        return $data;
    }
}
