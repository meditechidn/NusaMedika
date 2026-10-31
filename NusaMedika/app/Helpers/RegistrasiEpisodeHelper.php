<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * Satu episode registrasi = rangkaian registrasi yang terhubung lewat
 * referensi_registrasi_id (mis. IGD -> Rawat Inap -> Rawat Jalan).
 */
class RegistrasiEpisodeHelper
{
    public static function rootId(int $registrasiId): int
    {
        $current = DB::table('registrasi')
            ->where('registrasi_id', $registrasiId)
            ->first(['registrasi_id', 'referensi_registrasi_id']);

        if (! $current) {
            return $registrasiId;
        }

        $dilihat = [];

        while ($current->referensi_registrasi_id && ! isset($dilihat[$current->registrasi_id])) {
            $dilihat[$current->registrasi_id] = true;
            $induk = DB::table('registrasi')
                ->where('registrasi_id', $current->referensi_registrasi_id)
                ->first(['registrasi_id', 'referensi_registrasi_id']);

            if (! $induk) {
                break;
            }

            $current = $induk;
        }

        return (int) $current->registrasi_id;
    }

    /**
     * @return array{root_id:int, ids:array<int,int>}
     */
    public static function resolve(int $registrasiId): array
    {
        $rootId = static::rootId($registrasiId);

        return ['root_id' => $rootId, 'ids' => static::memberIdsForRoot($rootId)];
    }

    /**
     * @return array<int,int>
     */
    public static function memberIds(int $registrasiId): array
    {
        return static::resolve($registrasiId)['ids'];
    }

    /**
     * @return array<int,int>
     */
    public static function memberIdsForRoot(int $rootId): array
    {
        return static::membersForRoots([$rootId])[$rootId] ?? [$rootId];
    }

    /**
     * Peta root_id => seluruh registrasi_id anggota (termasuk root).
     *
     * @param  array<int,int>  $rootIds
     * @return array<int,array<int,int>>
     */
    public static function membersForRoots(array $rootIds): array
    {
        $rootIds = array_values(array_unique(array_map('intval', $rootIds)));
        if ($rootIds === []) {
            return [];
        }

        $pemilik = [];
        foreach ($rootIds as $rootId) {
            $pemilik[$rootId] = $rootId;
        }

        $frontier = $rootIds;
        $dilihat = [];

        while ($frontier !== []) {
            $anak = DB::table('registrasi')
                ->whereIn('referensi_registrasi_id', $frontier)
                ->get(['registrasi_id', 'referensi_registrasi_id']);

            $frontierBaru = [];
            foreach ($anak as $row) {
                $id = (int) $row->registrasi_id;
                $induk = (int) $row->referensi_registrasi_id;

                if (isset($dilihat[$id]) || ! isset($pemilik[$induk])) {
                    continue;
                }

                $dilihat[$id] = true;
                $pemilik[$id] = $pemilik[$induk];
                $frontierBaru[] = $id;
            }

            $frontier = $frontierBaru;
        }

        $peta = array_fill_keys($rootIds, []);
        foreach ($pemilik as $id => $rootId) {
            $peta[$rootId][] = $id;
        }

        foreach ($peta as &$ids) {
            sort($ids);
        }
        unset($ids);

        return $peta;
    }
}
