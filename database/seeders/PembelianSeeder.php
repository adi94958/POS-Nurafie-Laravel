<?php

namespace Database\Seeders;

use App\Models\Kasir;
use App\Models\Pemasok;
use App\Models\Pembayaran;
use App\Models\PembayaranPembelian;
use App\Models\Pembelian;
use App\Models\PembelianDetail;
use App\Models\Produk;
use App\Models\TipeTransfer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;
use Illuminate\Support\Str;

class PembelianSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Simulate sales transactions
        $this->generateSalesTransactions($faker);
    }

    protected function generateSalesTransactions($faker)
    {
        $produks = Produk::get();
        $idPemilik = Kasir::find(1)->id_pemilik ?? 1;
        $startDate = Carbon::now()->subYear()->startOfDay();
        $endDate = Carbon::now()->endOfDay();
        $currentDay = null;
        $dailyCounter = 1;

        while ($startDate->lte($endDate)) {
            if ($currentDay != $startDate->format('Ymd')) {
                $currentDay = $startDate->format('Ymd');
                $dailyCounter = 1;
            }

            $jumlahTransaksi = 1;

            for ($i = 0; $i < $jumlahTransaksi; $i++) {
                $tanggal = $startDate->copy()->setTime(rand(8, 18), rand(0, 59), 0);
                $produk = $produks->random();
                $jumlah = rand(1, 3);

                // Get price from level harga
                $hargaBeli = $produk->harga_beli;

                $total = $jumlah * $hargaBeli;
                $total = round($total / 5000) * 5000;

                $bayar = $faker->randomElement([
                    $total,
                    max(0, round(($total - rand(5000, 20000)) / 5000) * 5000)
                ]);
                $metode = $faker->randomElement(['tunai', 'transfer', 'utang']);

                $transfer = $this->getTransferDetails($faker, $metode);

                $transactionData = [
                    'total_harga' => $total,
                    'total_bayar' => $metode === 'utang' ? null : $bayar,
                    'tanggal_pembelian' => $tanggal,
                    'is_diproses' => $faker->boolean(10),
                    'jenis_pembayaran' => $metode,
                    'metode_transfer' => $transfer['metode_transfer'],
                    'jenis_transfer' => $transfer['jenis_transfer'],
                    'details' => [
                        [
                            'id_produk' => $produk->id_produk,
                            'jumlah_produk' => $jumlah,
                        ]
                    ]
                ];

                $idPembelian = 'INV-' . $idPemilik . $tanggal->format('Ymd') . str_pad($dailyCounter++, 3, '0', STR_PAD_LEFT);

                $this->createTransaction($transactionData, $idPembelian);
            }

            $startDate->addDay(4);
        }
    }


    protected function getTransferDetails($faker, $metode)
    {
        if ($metode === 'transfer') {
            // Get a random transfer type from the database
            $tipeTransfer = TipeTransfer::inRandomOrder()->first();

            if ($tipeTransfer) {
                return [
                    'metode_transfer' => $tipeTransfer->metode_transfer,
                    'jenis_transfer' => $tipeTransfer->jenis_transfer
                ];
            }

            // Fallback if no transfer types exist in the database
            return [
                'metode_transfer' => 'bank',
                'jenis_transfer' => 'BRI'
            ];
        }

        return ['metode_transfer' => null, 'jenis_transfer' => null];
    }

    protected function createTransaction($data, $idPembelian)
    {
        DB::transaction(function () use ($data, $idPembelian) {
            $status = $this->determineStatus($data);

            // Create pembelian record
            Pembelian::create([
                'id_pembelian' => $idPembelian,
                'id_pemasok' => Pemasok::where('id_pemilik', 1)->inRandomOrder()->value('id_pemasok'),
                'total_harga' => $data['total_harga'],
                'status_pembelian' => $status,
                'tanggal_kedatangan' => $data['tanggal_pembelian'],
                'created_at' => $data['tanggal_pembelian'],
                'updated_at' => $data['tanggal_pembelian']
            ]);

            // Create pembelian details and stock records
            foreach ($data['details'] as $detail) {
                PembelianDetail::create([
                    'id_pembelian' => $idPembelian,
                    'id_produk' => $detail['id_produk'],
                    'jumlah_produk' => $detail['jumlah_produk'],
                ]);

                if ($status !== 'diproses') {
                    DB::table('stok')->insert([
                        'id_produk' => $detail['id_produk'],
                        'jumlah_stok' => $detail['jumlah_produk'],
                        'jenis_stok' => 'In',
                        'jenis_transaksi' => 'Pembelian',
                        'keterangan' => 'Stok keluar dari penjualan #' . $idPembelian,
                        'created_at' => $data['tanggal_pembelian'],
                        'updated_at' => $data['tanggal_pembelian']
                    ]);
                }
            }

            // Create payment record if not utang
            if (strtolower($data['jenis_pembayaran']) !== 'utang' && isset($data['total_bayar'])) {
                $this->createPaymentRecord($data, $idPembelian, $status);
            }
        });
    }

    protected function determineStatus($data)
    {
        if ($data['is_diproses']) {
            return 'diproses';
        }

        return ($data['total_bayar'] ?? 0) >= $data['total_harga'] ? 'lunas' : 'belum lunas';
    }

    protected function createPaymentRecord($data, $idPembelian, $status)
    {
        $tipeTransfer = $this->getMetodePembayaran($data);

        $pembayaran = Pembayaran::create([
            'total_bayar' => $data['total_bayar'],
            'keterangan' => $status === 'lunas' ? 'Lunas' : 'Bayar Sebagian',
            'id_tipe_transfer' => $tipeTransfer->id_tipe_transfer ?? null,
            'jenis_pembayaran' => $data['jenis_pembayaran'],
            'created_at' => $data['tanggal_pembelian'],
            'updated_at' => $data['tanggal_pembelian']
        ]);

        PembayaranPembelian::create([
            'id_pembelian' => $idPembelian,
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'created_at' => $data['tanggal_pembelian'],
            'updated_at' => $data['tanggal_pembelian']
        ]);
    }

    protected function getMetodePembayaran($data)
    {
        if (strtolower($data['jenis_pembayaran']) === 'transfer') {
            $tipeTransfer = TipeTransfer::where('metode_transfer', $data['metode_transfer'])
                ->where('jenis_transfer', $data['jenis_transfer'])
                ->first();

            if (!$tipeTransfer) {
                throw new \Exception("Tipe transfer tidak ditemukan: metode = {$data['metode_transfer']}, jenis = {$data['jenis_transfer']}");
            }

            return $tipeTransfer;
        }

        return null;
    }
}
