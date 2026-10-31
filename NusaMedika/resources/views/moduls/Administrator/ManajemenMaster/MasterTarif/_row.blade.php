@php
    $tarifDefault = $t->tarif->firstWhere('kelas_ruang_id', null);
    $kelasTerisi = $t->tarif->filter(fn ($h) => $h->kelas_ruang_id !== null);
    $tarifDefaultValue = $tarifDefault ? (float) $tarifDefault->tarif : null;
@endphp
<tr class="hover:bg-blue-50/40 transition-colors">
    <td class="px-3 py-3 text-center text-slate-500">{{ $noUrut }}</td>
    <td class="px-3 py-3"><span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">{{ $t->kode_tindakan }}</span></td>
    <td class="px-3 py-3 font-semibold text-slate-800">{{ $t->nama_tindakan }}</td>
    <td class="px-3 py-3 text-center">
        @if ($t->kategori)
            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 text-violet-700">{{ $t->kategori->nama_kategori_tindakan }}</span>
        @else
            <span class="text-slate-400">-</span>
        @endif
    </td>
    <td class="px-3 py-3 text-right font-semibold text-slate-700">{{ $tarifDefaultValue !== null ? number_format($tarifDefaultValue, 0, ',', '.') : '-' }}</td>
    <td class="px-3 py-3 text-center">
        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $kelasTerisi->isEmpty() ? 'bg-slate-100 text-slate-500' : 'bg-cyan-100 text-cyan-700' }}">{{ $kelasTerisi->count() }}</span>
    </td>
    <td class="px-3 py-3 text-center">
        <div class="flex items-center justify-center gap-1">
            <a href="{{ route('admin.master_tarif.edit', $t->tindakan_id) }}" class="cursor-pointer p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-600 rounded-md transition-colors" title="Atur Tarif">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </a>
            @if ($tarifDefault)
                <form action="{{ route('admin.master_tarif.destroy', $t->tindakan_id) }}" method="POST" data-confirm-message="Yakin ingin menghapus seluruh tarif tindakan ini?" data-confirm-danger="true" data-confirm-title="Hapus Tarif" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="cursor-pointer p-1.5 text-red-500 hover:bg-red-50 hover:text-red-600 rounded-md transition-colors" title="Hapus Tarif">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </form>
            @endif
        </div>
    </td>
</tr>
