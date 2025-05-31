<?php

namespace Database\Seeders;

use App\Models\Kasir;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\PembayaranPenjualan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\Produk;
use App\Models\TipeTransfer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class PenjualanSeeder extends Seeder
{
    public function run()
    {

        $faker = Faker::create();

        // Add initial stock for all products
        $this->addInitialStock();

        // Simulate sales transactions
        $this->generateSalesTransactions($faker);

        // Generate manual transactions
        $this->generateManualTransactions();
    }

    protected function addInitialStock()
    {
        $produks = Produk::all();

        foreach ($produks as $produk) {
            DB::table('stok')->insert([
                'id_produk' => $produk->id_produk,
                'jumlah_stok' => 200,
                'jenis_stok' => 'In',
                'jenis_transaksi' => 'Manual',
                'created_at' => now()->subYear(),
                'updated_at' => now()
            ]);
        }
    }

    protected function generateSalesTransactions($faker)
    {
        $produks = Produk::with('level_hargas')->get();
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

            $jumlahTransaksi = rand(1, 5);

            for ($i = 0; $i < $jumlahTransaksi; $i++) {
                $tanggal = $startDate->copy()->setTime(rand(8, 18), rand(0, 59), 0);
                $produk = $produks->random();
                $jumlah = rand(1, 3);

                // Get price from level harga
                $levelHarga = $produk->level_hargas()
                    ->whereRaw('LOWER(nama_level) = ?', ['standart'])
                    ->first();

                $harga = $levelHarga
                    ? $levelHarga->harga_jual
                    : round(($produk->harga_beli * 1.2) / 1000) * 1000;

                $total = $jumlah * $harga;
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
                    'tanggal_penjualan' => $tanggal,
                    'is_pesanan' => $faker->boolean(10),
                    'jenis_pembayaran' => $metode,
                    'metode_transfer' => $transfer['metode_transfer'],
                    'jenis_transfer' => $transfer['jenis_transfer'],
                    'details' => [
                        [
                            'id_produk' => $produk->id_produk,
                            'jumlah_produk' => $jumlah,
                            'harga_jual' => $harga
                        ]
                    ]
                ];

                $idPenjualan = 'INV-' . $idPemilik . $tanggal->format('Ymd') . str_pad($dailyCounter++, 3, '0', STR_PAD_LEFT);

                $this->createTransaction($transactionData, $idPenjualan);
            }

            $startDate->addDay();
        }
    }

    protected function generateManualTransactions()
    {
        $manualProducts = [
            'Tas Ihram',
            'Koper Haji',
            'Kain Ihram',
            'Al-Qur\'an Mini',
            'Jubah Haji'
        ];

        $idPemilik = Kasir::find(1)->id_pemilik ?? 1;
        $tanggal = now();
        $currentDay = $tanggal->format('Ymd');

        // Find the highest counter for today's transactions
        $latestTransaction = Penjualan::where('id_penjualan', 'like', "INV-{$idPemilik}{$currentDay}%")
            ->orderBy('id_penjualan', 'desc')
            ->first();

        // Extract the counter number
        $startCounter = 1;
        if ($latestTransaction) {
            $counterStr = substr($latestTransaction->id_penjualan, -3);
            $startCounter = (int)$counterStr + 1;
        }

        foreach ($manualProducts as $index => $namaProduk) {
            $jumlah = rand(1, 3);
            $harga = rand(10, 40) * 5000;

            $total = $jumlah * $harga;
            $total = round($total / 5000) * 5000;

            // Generate ID continuing from the last used counter
            $currentCounter = $startCounter + $index;
            $idPenjualan = 'INV-' . $idPemilik . $currentDay . str_pad($currentCounter, 3, '0', STR_PAD_LEFT);

            DB::transaction(function () use ($idPenjualan, $namaProduk, $jumlah, $harga, $total, $tanggal) {
                Penjualan::create([
                    'id_penjualan' => $idPenjualan,
                    'id_kasir' => Kasir::where('id_pemilik', 1)->inRandomOrder()->value('id_kasir'),
                    'id_pelanggan' => Pelanggan::where('id_pemilik', 1)->inRandomOrder()->value('id_pelanggan'),
                    'total_harga' => $total,
                    'status_penjualan' => 'lunas',
                    'diskon' => 0,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal
                ]);

                PenjualanDetail::create([
                    'id_penjualan' => $idPenjualan,
                    'id_produk' => null,
                    'jumlah_produk' => $jumlah,
                    'nama_produk' => $namaProduk,
                    'harga_jual' => $harga,
                    'status_retur' => false,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ]);

                Pembayaran::create([
                    'total_bayar' => $total,
                    'keterangan' => 'Lunas',
                    'id_tipe_transfer' => null,
                    'jenis_pembayaran' => 'tunai',
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ]);

                // Jika kamu ingin menghubungkan pembayaran dengan penjualan:
                $pembayaran = Pembayaran::latest()->first();

                PembayaranPenjualan::create([
                    'id_penjualan' => $idPenjualan,
                    'id_pembayaran' => $pembayaran->id_pembayaran,
                    'created_at' => $tanggal,
                    'updated_at' => $tanggal,
                ]);
            });
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

    protected function createTransaction($data, $idPenjualan)
    {
        DB::transaction(function () use ($data, $idPenjualan) {
            $status = $this->determineStatus($data);

            // Create penjualan record
            Penjualan::create([
                'id_penjualan' => $idPenjualan,
                'id_kasir' => Kasir::where('id_pemilik', 1)->inRandomOrder()->value('id_kasir'),
                'id_pelanggan' => Pelanggan::where('id_pemilik', 1)->inRandomOrder()->value('id_pelanggan'),
                'total_harga' => $data['total_harga'],
                'status_penjualan' => $status,
                'diskon' => 0,
                'created_at' => $data['tanggal_penjualan'],
                'updated_at' => $data['tanggal_penjualan']
            ]);

            // Create penjualan details and stock records
            foreach ($data['details'] as $detail) {
                PenjualanDetail::create([
                    'id_penjualan' => $idPenjualan,
                    'id_produk' => $detail['id_produk'],
                    'jumlah_produk' => $detail['jumlah_produk'],
                    'harga_jual' => $detail['harga_jual'],
                    'status_retur' => 0
                ]);

                if ($status !== 'pesanan') {
                    DB::table('stok')->insert([
                        'id_produk' => $detail['id_produk'],
                        'jumlah_stok' => $detail['jumlah_produk'],
                        'jenis_stok' => 'Out',
                        'jenis_transaksi' => 'Penjualan',
                        'keterangan' => 'Stok keluar dari penjualan #' . $idPenjualan,
                        'created_at' => $data['tanggal_penjualan'],
                        'updated_at' => $data['tanggal_penjualan']
                    ]);
                }
            }

            // Create payment record if not utang
            if (strtolower($data['jenis_pembayaran']) !== 'utang' && isset($data['total_bayar'])) {
                $this->createPaymentRecord($data, $idPenjualan, $status);
            }
        });
    }

    protected function determineStatus($data)
    {
        if ($data['is_pesanan']) {
            return 'pesanan';
        }

        return ($data['total_bayar'] ?? 0) >= $data['total_harga'] ? 'lunas' : 'belum lunas';
    }

    protected function createPaymentRecord($data, $idPenjualan, $status)
    {
        $tipeTransfer = $this->getMetodePembayaran($data);

        $pembayaran = Pembayaran::create([
            'total_bayar' => $data['total_bayar'],
            'keterangan' => $status === 'lunas' ? 'Lunas' : 'Bayar Sebagian',
            'id_tipe_transfer' => $tipeTransfer->id_tipe_transfer ?? null,
            'jenis_pembayaran' => $data['jenis_pembayaran'],
            'created_at' => $data['tanggal_penjualan'],
            'updated_at' => $data['tanggal_penjualan']
        ]);

        PembayaranPenjualan::create([
            'id_penjualan' => $idPenjualan,
            'id_pembayaran' => $pembayaran->id_pembayaran,
            'created_at' => $data['tanggal_penjualan'],
            'updated_at' => $data['tanggal_penjualan']
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
