<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArusKeuangan extends Model
{
    protected $table = 'arus_keuangan';
    protected $primaryKey = 'id_arus_keuangan';

    protected $fillable = [
        'id_pemilik',
        'id_sumber',
        'keterangan',
        'jenis_transaksi',
        'nominal',
    ];

    public function getNominalDebitAttribute()
    {
        return $this->jenis_transaksi === 'debit' ? $this->nominal : '-';
    }

    public function getNominalKreditAttribute()
    {
        if ($this->jenis_transaksi === 'kredit' && !is_null($this->nominal)) {
            return abs($this->nominal);
        }
        return '-';
    }

    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class, 'id_sumber', 'id_pembayaran');
    }

    /**
     * Calculate running balance based on tab filter
     */
    public function calculateRunningBalance($tabFilter = 'Semua', $dateFrom = null, $dateUntil = null)
    {
        $query = self::query()
            ->where(function ($q) {
                $q->where('created_at', '<', $this->created_at)
                    ->orWhere(function ($q2) {
                        $q2->where('created_at', $this->created_at)
                            ->where('id_arus_keuangan', '<=', $this->id_arus_keuangan);
                    });
            });

        // Jangan filter berdasarkan dateFrom dan dateUntil
        // karena running balance harus menghitung semua sebelum transaksi ini

        // Filter berdasarkan tab (jenis pembayaran)
        if ($tabFilter === 'Tunai') {
            $query->whereHas('pembayaran', function ($q) {
                $q->where('jenis_pembayaran', 'tunai');
            });
        } elseif ($tabFilter === 'Transfer') {
            $query->whereHas('pembayaran', function ($q) {
                $q->where('jenis_pembayaran', 'transfer');
            });
        }

        $transactions = $query->orderBy('created_at', 'asc')
            ->orderBy('id_arus_keuangan', 'asc')
            ->get();

        $balance = 0;
        foreach ($transactions as $transaction) {
            if ($transaction->jenis_transaksi === 'kredit') {
                $balance -= abs($transaction->nominal);
            } elseif ($transaction->jenis_transaksi === 'debit') {
                $balance += abs($transaction->nominal);
            }
        }

        return $balance;
    }


    /**
     * Get saldo attribute (for backward compatibility)
     */
    public function getSaldoAttribute()
    {
        return $this->calculateRunningBalance('Semua');
    }

    /**
     * Get saldo stat
     */
    public static function getSaldoStat(?string $idPemilik, ?string $from = null, ?string $until = null, ?string $jenisPembayaran = null): array
    {
        $query = static::query()
            ->where('id_pemilik', $idPemilik);

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($until) {
            $query->whereDate('created_at', '<=', $until);
        }

        if ($jenisPembayaran && $jenisPembayaran !== 'Semua') {
            $query->whereHas('pembayaran', function ($q) use ($jenisPembayaran) {
                $q->where('jenis_pembayaran', strtolower($jenisPembayaran));
            });
        }

        $totalDebit = (clone $query)->where('jenis_transaksi', 'debit')->sum('nominal');
        $totalKredit = (clone $query)->where('jenis_transaksi', 'kredit')->sum('nominal');

        return [
            'total_debit' => $totalDebit,
            'total_kredit' => $totalKredit,
            'saldo' => $totalDebit - $totalKredit,
        ];
    }
}
