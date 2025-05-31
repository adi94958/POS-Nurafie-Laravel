<?php

namespace Database\Seeders;

use App\Models\Pelanggan;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class PelangganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('id_ID');

        $data = [];
        for ($i = 0; $i < 10; $i++) {
            $totalLength = 12;
            $remainingDigits = $totalLength - 2;
            $number = '08' . $faker->numerify(str_repeat('#', $remainingDigits));

            $data[] = [
                'nama_pelanggan' => $faker->name,
                'id_pemilik' => 1,
                'no_telp' => $number,
                'alamat' => $faker->address,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Pelanggan::insert($data);
    }
}
