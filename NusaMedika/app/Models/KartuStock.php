<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuStock extends Model
{
    protected $table = 'kartu_stock';

    protected $primaryKey = 'kartu_stock_id';

    public $timestamps = false;

    protected $fillable = [
        'barang_id',
        'no_batch',
        'bagian_id',
        'tanggal',
        'jenis_mutasi',
        'ref_penerimaan_detail_id',
        'ref_mutasi_barang_detail_id',
        'ref_peresepan_obat_detail_id',
        'ref_emr_id',
        'qty_masuk',
        'qty_keluar',
        'saldo_sebelum',
        'saldo_sesudah',
        'harga_beli',
        'harga_jual',
        'tgl_expired',
        'keterangan',
    ];

    public const JENIS_SALDO_AWAL = 0;

    public const JENIS_PENERIMAAN = 1;

    public const JENIS_MUTASI_MASUK = 2;

    public const JENIS_MUTASI_KELUAR = 3;

    public const JENIS_PEMAKAIAN = 4;

    public const JENIS_DISPENSE_OBAT = 5;

    public const JENIS_PENYESUAIAN = 6;

    public const JENIS_LABEL = [
        self::JENIS_SALDO_AWAL => 'Saldo Awal',
        self::JENIS_PENERIMAAN => 'Penerimaan',
        self::JENIS_MUTASI_MASUK => 'Mutasi Masuk',
        self::JENIS_MUTASI_KELUAR => 'Mutasi Keluar',
        self::JENIS_PEMAKAIAN => 'Pemakaian',
        self::JENIS_DISPENSE_OBAT => 'Dispense Obat',
        self::JENIS_PENYESUAIAN => 'Penyesuaian Stock Opname',
    ];

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('status_batal')->orWhere('status_batal', 0);
        });
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'barang_id', 'barang_id');
    }

    public function bagian(): BelongsTo
    {
        return $this->belongsTo(Bagian::class, 'bagian_id', 'bagian_id');
    }

    public function penerimaanDetail(): BelongsTo
    {
        return $this->belongsTo(PenerimaanDetail::class, 'ref_penerimaan_detail_id', 'penerimaan_detail_id');
    }

    public function mutasiBarangDetail(): BelongsTo
    {
        return $this->belongsTo(MutasiBarangDetail::class, 'ref_mutasi_barang_detail_id', 'mutasi_barang_detail_id');
    }

    public function peresepanObatDetail(): BelongsTo
    {
        return $this->belongsTo(PeresepanObatDetail::class, 'ref_peresepan_obat_detail_id', 'peresepan_obat_detail_id');
    }

    public function getJenisLabelAttribute(): string
    {
        return self::JENIS_LABEL[$this->jenis_mutasi ?? 0] ?? '-';
    }
}
