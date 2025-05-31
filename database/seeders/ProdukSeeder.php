<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produk;
use App\Models\LevelHarga;
use App\Models\Stok;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProdukSeeder extends Seeder
{
    public function run(): void
    {
        // Seed Kategori terlebih dahulu
        $this->seedKategori();

        // Seed Satuan
        $this->seedSatuan();

        // Seed Produk
        $this->seedProduk();

        // Seed Level Harga
        $this->seedLevelHarga();

        // Seed initial stock records
        $this->seedStokAwal();
    }

    private function seedKategori(): void
    {
        // Check if data exists
        if (DB::table('kategori')->count() > 0) {
            Schema::disableForeignKeyConstraints();
            DB::table('kategori')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        DB::table('kategori')->insert([
            ['id_kategori' => 1, 'nama_kategori' => 'Sajadah', 'id_pemilik' => 1],
            ['id_kategori' => 2, 'nama_kategori' => 'Sorban', 'id_pemilik' => 1],
            ['id_kategori' => 3, 'nama_kategori' => 'Kurma & Makanan', 'id_pemilik' => 1],
            ['id_kategori' => 4, 'nama_kategori' => 'Air Zamzam', 'id_pemilik' => 1],
            ['id_kategori' => 5, 'nama_kategori' => 'Peci & Kopiah', 'id_pemilik' => 1],
            ['id_kategori' => 6, 'nama_kategori' => 'Mukena & Telekung', 'id_pemilik' => 1],
            ['id_kategori' => 7, 'nama_kategori' => 'Pakaian Ihram', 'id_pemilik' => 1],
            ['id_kategori' => 8, 'nama_kategori' => 'Parfum & Minyak', 'id_pemilik' => 1],
            ['id_kategori' => 9, 'nama_kategori' => 'Tasbih & Aksesoris', 'id_pemilik' => 1],
            ['id_kategori' => 10, 'nama_kategori' => 'Herbal & Suplemen', 'id_pemilik' => 1],
        ]);
    }

    private function seedSatuan(): void
    {
        // Check if data exists
        if (DB::table('satuan')->count() > 0) {
            Schema::disableForeignKeyConstraints();
            DB::table('satuan')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        DB::table('satuan')->insert([
            ['id_satuan' => 1, 'nama_satuan' => 'Pcs', 'id_pemilik' => null],
            ['id_satuan' => 2, 'nama_satuan' => 'Kg', 'id_pemilik' => null],
            ['id_satuan' => 3, 'nama_satuan' => 'Liter', 'id_pemilik' => null],
            ['id_satuan' => 4, 'nama_satuan' => 'Dus', 'id_pemilik' => 1],
            ['id_satuan' => 5, 'nama_satuan' => 'Sachet', 'id_pemilik' => 1],
            ['id_satuan' => 6, 'nama_satuan' => 'Set', 'id_pemilik' => 1],
            ['id_satuan' => 7, 'nama_satuan' => 'Botol', 'id_pemilik' => 1],
            ['id_satuan' => 8, 'nama_satuan' => 'Pack', 'id_pemilik' => 1],
        ]);
    }

    private function seedProduk(): void
    {
        // Check if data exists
        if (DB::table('produk')->count() > 0) {
            Schema::disableForeignKeyConstraints();
            DB::table('produk')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        $produkList = [
            // Sajadah
            ['nama_produk' => 'Sajadah Turki Premium', 'id_kategori' => 1, 'id_satuan' => 1, 'stok_minimum' => 5, 'harga_beli' => 45000],
            ['nama_produk' => 'Sajadah Madinah Motif Kabah', 'id_kategori' => 1, 'id_satuan' => 1, 'stok_minimum' => 6, 'harga_beli' => 40000],
            ['nama_produk' => 'Sajadah Travel Lipat', 'id_kategori' => 1, 'id_satuan' => 1, 'stok_minimum' => 8, 'harga_beli' => 20000],

            // Sorban
            ['nama_produk' => 'Sorban Putih Madinah', 'id_kategori' => 2, 'id_satuan' => 1, 'stok_minimum' => 7, 'harga_beli' => 25000],
            ['nama_produk' => 'Sorban Arab Premium', 'id_kategori' => 2, 'id_satuan' => 1, 'stok_minimum' => 6, 'harga_beli' => 35000],
            ['nama_produk' => 'Sorban Oman Katun', 'id_kategori' => 2, 'id_satuan' => 1, 'stok_minimum' => 7, 'harga_beli' => 30000],

            // Kurma & Makanan
            ['nama_produk' => 'Kurma Ajwa Madinah', 'id_kategori' => 3, 'id_satuan' => 2, 'stok_minimum' => 10, 'harga_beli' => 60000],
            ['nama_produk' => 'Kurma Medjool Premium', 'id_kategori' => 3, 'id_satuan' => 2, 'stok_minimum' => 10, 'harga_beli' => 55000],
            ['nama_produk' => 'Kurma Sukari Box', 'id_kategori' => 3, 'id_satuan' => 4, 'stok_minimum' => 6, 'harga_beli' => 45000],
            ['nama_produk' => 'Kismis Iran Premium', 'id_kategori' => 3, 'id_satuan' => 2, 'stok_minimum' => 8, 'harga_beli' => 35000],
            ['nama_produk' => 'Madu Arab Asli', 'id_kategori' => 3, 'id_satuan' => 7, 'stok_minimum' => 8, 'harga_beli' => 50000],
            ['nama_produk' => 'Cokelat Arab Premium', 'id_kategori' => 3, 'id_satuan' => 4, 'stok_minimum' => 7, 'harga_beli' => 25000],

            // Air Zamzam
            ['nama_produk' => 'Air Zamzam Asli 5L', 'id_kategori' => 4, 'id_satuan' => 7, 'stok_minimum' => 6, 'harga_beli' => 50000],
            ['nama_produk' => 'Air Zamzam Asli 1L', 'id_kategori' => 4, 'id_satuan' => 7, 'stok_minimum' => 20, 'harga_beli' => 12000],
            ['nama_produk' => 'Air Zamzam Botol Souvenir', 'id_kategori' => 4, 'id_satuan' => 7, 'stok_minimum' => 15, 'harga_beli' => 8000],

            // Peci & Kopiah
            ['nama_produk' => 'Peci Rajut Haji Putih', 'id_kategori' => 5, 'id_satuan' => 1, 'stok_minimum' => 10, 'harga_beli' => 12000],
            ['nama_produk' => 'Peci Haji Turki', 'id_kategori' => 5, 'id_satuan' => 1, 'stok_minimum' => 8, 'harga_beli' => 18000],
            ['nama_produk' => 'Songkok Haji Premium', 'id_kategori' => 5, 'id_satuan' => 1, 'stok_minimum' => 6, 'harga_beli' => 25000],

            // Mukena & Telekung
            ['nama_produk' => 'Mukena Bordir Mekah', 'id_kategori' => 6, 'id_satuan' => 6, 'stok_minimum' => 6, 'harga_beli' => 70000],
            ['nama_produk' => 'Mukena Katun Madinah', 'id_kategori' => 6, 'id_satuan' => 6, 'stok_minimum' => 5, 'harga_beli' => 60000],
            ['nama_produk' => 'Telekung Travel Premium', 'id_kategori' => 6, 'id_satuan' => 6, 'stok_minimum' => 6, 'harga_beli' => 45000],

            // Pakaian Ihram
            ['nama_produk' => 'Baju Ihram Pria', 'id_kategori' => 7, 'id_satuan' => 6, 'stok_minimum' => 8, 'harga_beli' => 55000],
            ['nama_produk' => 'Baju Ihram Wanita', 'id_kategori' => 7, 'id_satuan' => 6, 'stok_minimum' => 8, 'harga_beli' => 60000],
            ['nama_produk' => 'Ikat Pinggang Ihram', 'id_kategori' => 7, 'id_satuan' => 1, 'stok_minimum' => 10, 'harga_beli' => 15000],

            // Parfum & Minyak
            ['nama_produk' => 'Parfum Non Alkohol Mekkah', 'id_kategori' => 8, 'id_satuan' => 7, 'stok_minimum' => 12, 'harga_beli' => 25000],
            ['nama_produk' => 'Minyak Wangi Arab Kasturi', 'id_kategori' => 8, 'id_satuan' => 7, 'stok_minimum' => 10, 'harga_beli' => 30000],
            ['nama_produk' => 'Minyak Zaitun Palestina', 'id_kategori' => 8, 'id_satuan' => 7, 'stok_minimum' => 10, 'harga_beli' => 25000],

            // Tasbih & Aksesoris
            ['nama_produk' => 'Tasbih Kayu Kokka', 'id_kategori' => 9, 'id_satuan' => 1, 'stok_minimum' => 12, 'harga_beli' => 15000],
            ['nama_produk' => 'Tasbih Batu Akik Arab', 'id_kategori' => 9, 'id_satuan' => 1, 'stok_minimum' => 8, 'harga_beli' => 25000],
            ['nama_produk' => 'Cincin Aqiq Yaman', 'id_kategori' => 9, 'id_satuan' => 1, 'stok_minimum' => 6, 'harga_beli' => 30000],
            ['nama_produk' => 'Siwak Natural', 'id_kategori' => 9, 'id_satuan' => 1, 'stok_minimum' => 20, 'harga_beli' => 3000],

            // Herbal & Suplemen
            ['nama_produk' => 'Habbatussauda Kapsul', 'id_kategori' => 10, 'id_satuan' => 5, 'stok_minimum' => 20, 'harga_beli' => 15000],
            ['nama_produk' => 'Bubuk Bidara Arab', 'id_kategori' => 10, 'id_satuan' => 5, 'stok_minimum' => 15, 'harga_beli' => 12000],
            ['nama_produk' => 'Madu Herbal Multivitamin', 'id_kategori' => 10, 'id_satuan' => 7, 'stok_minimum' => 10, 'harga_beli' => 30000],
        ];

        $data = [];

        foreach ($produkList as $item) {

            $data[] = [
                'nama_produk' => $item['nama_produk'],
                'id_kategori' => $item['id_kategori'],
                'id_satuan' => $item['id_satuan'],
                'harga_beli' => $item['harga_beli'],
                'id_pemilik' => 1,
                'stok_minimum' => $item['stok_minimum'],
                'deskripsi' => $this->generateDeskripsi($item['nama_produk']),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Produk::insert($data);
    }

    private function seedLevelHarga(): void
    {
        // Check if data exists
        if (DB::table('level_harga')->count() > 0) {
            Schema::disableForeignKeyConstraints();
            DB::table('level_harga')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        // Ambil semua produk yang sudah ada
        $produkList = Produk::all();

        foreach ($produkList as $produk) {
            // Level harga wajib: Standar (markup 10-25%)
            $markup = rand(110, 125) / 100;
            $hargaStandar = $produk->harga_beli * $markup;
            $hargaStandar = round($hargaStandar, -3); // Menyesuaikan agar 3 angka terakhir menjadi 0

            LevelHarga::create([
                'id_produk'  => $produk->id_produk,
                'nama_level' => 'Standart',
                'harga_jual' => $hargaStandar,
            ]);

            // Level harga "Grosir" (markup 5-15%)
            $markup = rand(105, 115) / 100;
            $hargaGrosir = $produk->harga_beli * $markup;
            $hargaGrosir = round($hargaGrosir, -3);

            LevelHarga::create([
                'id_produk'  => $produk->id_produk,
                'nama_level' => 'Grosir',
                'harga_jual' => $hargaGrosir,
            ]);

            // Level harga "Reseller" untuk produk tertentu (markup 2-8%)
            if ($produk->harga_beli > 100000) {
                $markup = rand(102, 108) / 100;
                $hargaReseller = $produk->harga_beli * $markup;
                $hargaReseller = round($hargaReseller, -3);

                LevelHarga::create([
                    'id_produk'  => $produk->id_produk,
                    'nama_level' => 'Reseller',
                    'harga_jual' => $hargaReseller,
                ]);
            }

            // Level harga "Premium" untuk produk mahal (markup 30-40%)
            if ($produk->harga_beli > 150000) {
                $markup = rand(130, 140) / 100;
                $hargaPremium = $produk->harga_beli * $markup;
                $hargaPremium = round($hargaPremium, -3);

                LevelHarga::create([
                    'id_produk'  => $produk->id_produk,
                    'nama_level' => 'Premium',
                    'harga_jual' => $hargaPremium,
                ]);
            }
        }
    }

    /**
     * Add initial stock records for all products
     */
    private function seedStokAwal(): void
    {
        // Clear existing stock data if any
        if (DB::table('stok')->count() > 0) {
            Schema::disableForeignKeyConstraints();
            DB::table('stok')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        $baseDate = now()->subMonths(2); // Set base date to 2 months ago

        $produkList = Produk::all();

        foreach ($produkList as $produk) {
            // Add initial stock record with 0 quantity
            $this->addStok($produk, 0, null, null, 'Stok Awal', $baseDate);
        }
    }

    /**
     * Helper method to add stock records
     */
    private function addStok($produk, $jumlahStok, $jenisStok, $jenisTransaksi, $keterangan, $tanggal = null)
    {
        Stok::create([
            'id_produk'       => $produk->id_produk,
            'jumlah_stok'     => $jumlahStok,
            'jenis_stok'      => $jenisStok,
            'jenis_transaksi' => $jenisTransaksi,
            'keterangan'      => $keterangan,
            'created_at'      => $tanggal ?? now(),
            'updated_at'      => $tanggal ?? now(),
        ]);
    }

    /**
     * Generate deskripsi yang lebih spesifik untuk setiap produk
     *
     * @param string $namaProduk
     * @return string
     */
    private function generateDeskripsi($namaProduk)
    {
        $deskripsi = [
            // Sajadah
            'Sajadah Turki Premium' => 'Sajadah berkualitas tinggi import langsung dari Turki dengan bahan lembut dan nyaman untuk ibadah. Terbuat dari katun terbaik dengan jahitan yang halus dan tahan lama.',
            'Sajadah Madinah Motif Kabah' => 'Sajadah elegan dengan motif Kabah khas Madinah. Bahan tebal dan lembut, tidak mudah kusut saat dilipat. Ukuran cukup luas untuk shalat dengan nyaman.',
            'Sajadah Travel Lipat' => 'Sajadah praktis untuk perjalanan ibadah umrah atau haji. Ringan dan mudah dilipat kompak sehingga tidak memakan tempat dalam koper. Dilengkapi kantong penyimpanan.',

            // Sorban
            'Sorban Putih Madinah' => 'Sorban putih asli dari Madinah dengan bahan katun halus dan nyaman dipakai. Panjang ideal untuk dililitkan dengan rapi di kepala.',
            'Sorban Arab Premium' => 'Sorban khas Arab dengan kualitas premium, terbuat dari bahan katun pilihan yang lembut dan nyaman. Tersedia dalam panjang 2 meter.',
            'Sorban Oman Katun' => 'Sorban tradisional dari Oman terbuat dari katun pilihan. Ringan dan nyaman dipakai untuk berbagai kesempatan ibadah maupun acara keagamaan.',

            // Kurma & Makanan
            'Kurma Ajwa Madinah' => 'Kurma Ajwa asli Madinah dengan rasa manis dan tekstur lembut. Terkenal dengan manfaat kesehatan dan khasiatnya yang disebutkan dalam hadits nabi.',
            'Kurma Medjool Premium' => 'Kurma Medjool besar dengan tekstur lembut dan rasa manis alami yang khas. Sering disebut sebagai "raja kurma" karena ukuran dan kelezatannya.',
            'Kurma Sukari Box' => 'Kurma Sukari kualitas premium dalam kemasan box eksklusif, cocok untuk hadiah. Memiliki rasa manis natural dengan tekstur yang tidak terlalu lembek.',
            'Kismis Iran Premium' => 'Kismis premium asal Iran dengan rasa manis alami dan kaya akan nutrisi. Diproses secara higienis tanpa tambahan gula atau pengawet.',
            'Madu Arab Asli' => 'Madu asli dari Arab dengan kualitas premium dan khasiat tinggi untuk kesehatan. Diambil langsung dari lebah yang memakan nektar bunga gurun Arab.',
            'Cokelat Arab Premium' => 'Cokelat premium khas Arab dengan cita rasa unik. Terbuat dari bahan pilihan, cocok untuk oleh-oleh atau hadiah spesial setelah umrah dan haji.',

            // Air Zamzam
            'Air Zamzam Asli 5L' => 'Air Zamzam asli yang didatangkan langsung dari Mekkah dengan kualitas terjamin. Dikemas dalam wadah 5 liter yang aman dan higienis.',
            'Air Zamzam Asli 1L' => 'Air Zamzam asli dalam kemasan praktis 1 liter. Didatangkan langsung dari sumur zamzam di Masjidil Haram, Mekkah.',
            'Air Zamzam Botol Souvenir' => 'Air Zamzam dalam botol souvenir cantik, ideal untuk oleh-oleh atau kenang-kenangan dari tanah suci. Isi 250ml dengan desain botol eksklusif.',

            // Peci & Kopiah
            'Peci Rajut Haji Putih' => 'Peci rajut khas untuk ibadah haji dengan bahan nyaman dan tidak mudah kusut. Menyerap keringat dengan baik dan tetap terasa sejuk.',
            'Peci Haji Turki' => 'Peci khas Turki untuk ibadah haji, terbuat dari bahan berkualitas tinggi dengan jahitan rapi. Nyaman dipakai dalam waktu lama.',
            'Songkok Haji Premium' => 'Songkok haji premium dengan desain elegan dan nyaman. Cocok dipakai selama ibadah haji dan umrah atau kegiatan ibadah sehari-hari.',

            // Mukena & Telekung
            'Mukena Bordir Mekah' => 'Mukena dengan bordir khas Mekah, elegan dan nyaman untuk beribadah. Terbuat dari katun berkualitas tinggi yang tidak menerawang dan sejuk.',
            'Mukena Katun Madinah' => 'Mukena dari katun lembut khas Madinah, ringan dan nyaman digunakan untuk ibadah. Dilengkapi tas cantik sebagai wadah praktis.',
            'Telekung Travel Premium' => 'Telekung ringan untuk perjalanan ibadah, mudah dilipat dan tidak memakan banyak tempat di koper. Terbuat dari bahan anti kusut.',

            // Pakaian Ihram
            'Baju Ihram Pria' => 'Baju ihram untuk laki-laki dengan bahan katun yang nyaman digunakan saat ibadah haji dan umrah. Terdiri dari dua lembar kain putih tanpa jahitan.',
            'Baju Ihram Wanita' => 'Pakaian ihram khusus wanita yang simpel dan sesuai syariat. Terbuat dari bahan yang tidak menerawang dan nyaman dipakai selama ritual ibadah.',
            'Ikat Pinggang Ihram' => 'Ikat pinggang khusus untuk pakaian ihram dengan desain praktis dan aman. Dilengkapi kantong tersembunyi untuk menyimpan uang dan dokumen penting.',

            // Parfum & Minyak
            'Parfum Non Alkohol Mekkah' => 'Parfum non alkohol dari Mekkah dengan aroma khas timur tengah yang tahan lama. Aman digunakan saat ibadah karena bebas alkohol.',
            'Minyak Wangi Arab Kasturi' => 'Minyak wangi dengan aroma kasturi khas Arab yang tahan lama. Dikemas dalam botol roll-on yang praktis dan mudah dibawa.',
            'Minyak Zaitun Palestina' => 'Minyak zaitun asli dari Palestina, dipanen dan diolah dengan metode tradisional. Bermanfaat untuk kesehatan dan perawatan tubuh.',

            // Tasbih & Aksesoris
            'Tasbih Kayu Kokka' => 'Tasbih dari kayu Kokka berkualitas dengan ukiran dan butiran halus. Ringan di tangan dan nyaman digunakan untuk dzikir.',
            'Tasbih Batu Akik Arab' => 'Tasbih premium dari batu akik khas Arab dengan butiran yang halus dan elegan. Setiap butir memiliki corak unik dan warna alami.',
            'Cincin Aqiq Yaman' => 'Cincin aqiq asli dari Yaman dengan batu berkualitas tinggi. Memiliki berbagai corak alami yang indah dan unik, cocok untuk oleh-oleh.',
            'Siwak Natural' => 'Siwak alami untuk kebersihan gigi dan mulut sesuai sunnah Rasulullah SAW. Dipanen dari pohon Salvadora persica asli Arab.',

            // Herbal & Suplemen
            'Habbatussauda Kapsul' => 'Suplemen habbatussauda dalam bentuk kapsul yang mudah dikonsumsi untuk kesehatan. Diproduksi dengan standar GMP dari biji jintan hitam pilihan.',
            'Bubuk Bidara Arab' => 'Bubuk daun bidara Arab yang dipercaya memiliki banyak manfaat untuk kesehatan dan ruqyah. Dikemas secara higienis dan praktis.',
            'Madu Herbal Multivitamin' => 'Madu herbal dengan tambahan multivitamin dan propolis untuk meningkatkan daya tahan tubuh. Ideal dikonsumsi rutin setelah kepulangan dari ibadah.',
        ];

        return $deskripsi[$namaProduk] ?? fake()->sentence(8);
    }
}
