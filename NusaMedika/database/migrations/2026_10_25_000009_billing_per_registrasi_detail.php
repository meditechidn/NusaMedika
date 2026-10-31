<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('billing', 'registrasi_detail_id')) {
            Schema::table('billing', function (Blueprint $table) {
                $table->integer('registrasi_detail_id')->nullable()->after('registrasi_id');
                $table->index('registrasi_detail_id');
            });
        }

        if (! Schema::hasColumn('billing_pembayaran', 'registrasi_id')) {
            Schema::table('billing_pembayaran', function (Blueprint $table) {
                $table->integer('registrasi_id')->nullable()->after('billing_id');
                $table->index('registrasi_id');
            });
        }

        if (! Schema::hasColumn('billing_pembayaran_detail', 'billing_id')) {
            Schema::table('billing_pembayaran_detail', function (Blueprint $table) {
                $table->integer('billing_id')->nullable()->after('billing_pembayaran_id');
                $table->index('billing_id');
            });
        }

        $this->backfillRegistrasiDetail();

        DB::table('billing_pembayaran')
            ->whereNull('registrasi_id')
            ->update([
                'registrasi_id' => DB::raw('(select b.registrasi_id from billing b where b.billing_id = billing_pembayaran.billing_id)'),
            ]);

        DB::table('billing_pembayaran_detail')
            ->whereNull('billing_id')
            ->update([
                'billing_id' => DB::raw('(select bp.billing_id from billing_pembayaran bp where bp.billing_pembayaran_id = billing_pembayaran_detail.billing_pembayaran_id)'),
            ]);

        DB::statement(<<<'SQL'
            WITH ranked AS (
                SELECT billing_id,
                       ROW_NUMBER() OVER (PARTITION BY registrasi_detail_id ORDER BY billing_id) AS rn
                FROM billing
                WHERE registrasi_detail_id IS NOT NULL
                  AND (status_batal IS NULL OR status_batal = 0)
            )
            UPDATE billing b
            SET registrasi_detail_id = NULL
            FROM ranked r
            WHERE b.billing_id = r.billing_id
              AND r.rn > 1
        SQL);

        if (! $this->hasIndex('billing', 'billing_registrasi_detail_aktif_unique')) {
            DB::statement('CREATE UNIQUE INDEX billing_registrasi_detail_aktif_unique ON billing (registrasi_detail_id) WHERE status_batal IS NULL OR status_batal = 0');
        }
    }

    public function down(): void
    {
        if ($this->hasIndex('billing', 'billing_registrasi_detail_aktif_unique')) {
            DB::statement('DROP INDEX IF EXISTS billing_registrasi_detail_aktif_unique');
        }

        if (Schema::hasColumn('billing_pembayaran_detail', 'billing_id')) {
            Schema::table('billing_pembayaran_detail', function (Blueprint $table) {
                $table->dropColumn('billing_id');
            });
        }

        if (Schema::hasColumn('billing_pembayaran', 'registrasi_id')) {
            Schema::table('billing_pembayaran', function (Blueprint $table) {
                $table->dropColumn('registrasi_id');
            });
        }

        if (Schema::hasColumn('billing', 'registrasi_detail_id')) {
            Schema::table('billing', function (Blueprint $table) {
                $table->dropColumn('registrasi_detail_id');
            });
        }
    }

    private function backfillRegistrasiDetail(): void
    {
        $billings = DB::table('billing')
            ->whereNull('registrasi_detail_id')
            ->select('billing_id', 'registrasi_id')
            ->get();

        foreach ($billings as $billing) {
            $dariDetail = DB::table('billing_detail')
                ->where('billing_id', $billing->billing_id)
                ->whereNotNull('registrasi_detail_id')
                ->distinct()
                ->pluck('registrasi_detail_id');

            if ($dariDetail->count() === 1) {
                DB::table('billing')->where('billing_id', $billing->billing_id)
                    ->update(['registrasi_detail_id' => $dariDetail->first()]);

                continue;
            }

            $details = DB::table('registrasi_detail')
                ->where('registrasi_id', $billing->registrasi_id)
                ->where(function ($q) {
                    $q->whereNull('status_batal')->orWhere('status_batal', 0);
                })
                ->pluck('registrasi_detail_id');

            if ($details->count() === 1) {
                DB::table('billing')->where('billing_id', $billing->billing_id)
                    ->update(['registrasi_detail_id' => $details->first()]);
            }
        }
    }

    private function hasIndex(string $table, string $index): bool
    {
        return DB::table('pg_indexes')
            ->where('schemaname', 'public')
            ->where('tablename', $table)
            ->where('indexname', $index)
            ->exists();
    }
};
