<?php

namespace Database\Seeders;

use App\Models\TipeTransfer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TipeTransferSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing data to avoid duplicates
        if (DB::table('tipe_transfer')->count() > 0) {
            Schema::disableForeignKeyConstraints();
            DB::table('tipe_transfer')->truncate();
            Schema::enableForeignKeyConstraints();
        }

        // Bank data with shorter names (avoiding truncation)
        $banks = [
            // Bank BUKU IV (Largest Indonesian Banks)
            'BCA',
            'Mandiri',
            'BRI',
            'BNI',

            // Bank BUKU III (Large Banks)
            'BTN',
            'CIMB Niaga',
            'Danamon',
            'Permata',
            'Panin',
            'OCBC NISP',
            'Maybank',
            'BTPN',

            // Syariah Banks
            'BSI',
            'Muamalat',
            'Mega Syariah',
            'BCA Syariah',
            'BJB Syariah',
            'CIMB Niaga Syariah',

            // Digital Banks
            'Jago',
            'Neo Commerce',
            'Jenius',
            'Digibank',
            'Blu',
            'Wokee',
            'LINE Bank',

            // Regional Development Banks (BPD)
            'BJB',
            'Bank DKI',
            'Bank Jatim',
            'Bank Jateng',
            'Bank Sumut',
            'Bank Nagari',
            'Bank Aceh Syariah',
            'Bank SulutGo',
        ];

        // E-wallet data with shorter names
        $eWallets = [
            // Major E-wallets
            'GoPay',
            'OVO',
            'DANA',
            'ShopeePay',
            'LinkAja',
            'QRIS',

            // Bank-Based E-wallets
            'Sakuku',
            'Jenius Pay',
            'Livin',
            'BRImo',
            'OCTO Mobile',
            'Neo+',

            // Other E-wallets
            'Doku',
            'iSaku',
            'Paytren',
            'Bluepay',

            // E-money Cards
            'Flazz',
            'BRIzzi',
            'TapCash',
            'e-Money',
            'JakCard',
        ];

        $data = [];

        // Add banks with proper format
        foreach ($banks as $bank) {
            $data[] = [
                'metode_transfer' => 'bank',
                'jenis_transfer' => $bank,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Add e-wallets with proper format
        foreach ($eWallets as $eWallet) {
            $data[] = [
                'metode_transfer' => 'e-wallet',  // Change to 'e-wallet' with hyphen
                'jenis_transfer' => $eWallet,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TipeTransfer::insert($data);
    }
}
