<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Bagian;
use Illuminate\Http\Request;

class ApiBagianController extends Controller
{
    public function searchBagian(Request $request)
    {
        $term = $request->input('q');

        $query = Bagian::aktif();

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('nama_bagian', 'ILIKE', "%{$term}%");
            });
        }

        $limit = $request->input('limit', 1000);
        $results = $query->limit($limit)->get()->map(function ($bagian) {
            return [
                'id' => $bagian->bagian_id,
                'text' => $bagian->nama_bagian,
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }
}
