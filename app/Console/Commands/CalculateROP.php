<?php

namespace App\Console\Commands;

use App\Models\Pemilik;
use App\ReorderPointService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateROP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-r-o-p';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hitung Safety Stock dan Reorder Point setiap awal bulan';

    protected function notifyROP(){
        try {
            $pemilikList = Pemilik::with('user')->get();
            foreach ($pemilikList as $pemilik) {
                $user = $pemilik->user;
                if ($user) {
                    Notification::make()
                        ->title('Perhitungan stok minimum telah dilakukan')
                        ->body(
                            "Perhitungan stok minimum dijalankan pada: " . now()
                        )
                        ->success()
                        ->sendToDatabase($user);
                        // ->broadcast($user);
                }
            }
            
        } catch (Exception $e) {
            Log::error('Error saat mengirim notifikasi: ' . $e->getMessage());
        }
    }
    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $result = (new \App\ReorderPointService)->calculate();
            $bulanProses = now()->translatedFormat('F Y');
            $jumlahProduk = isset($result['data']) ? count($result['data']) : 0;
            Log::info("ROP dijalankan pada: " . now() . " | Bulan: $bulanProses | Jumlah produk: $jumlahProduk");
            $this->notifyROP();
        } catch (\Throwable $e) {
            $this->error("Terjadi error: " . $e->getMessage());
            Log::error("Error saat menjalankan ROP: " . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }
}
