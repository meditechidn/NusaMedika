@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-end justify-between gap-4">
    <div>
        <h1 class="text-[22px] font-bold text-slate-900 tracking-tight">Edit Barang</h1>
        <p class="text-sm text-slate-500 mt-1">Perbarui data barang / obat pada master.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.barang.index') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors flex items-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Kembali
        </a>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <!-- Form Header -->
    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50/50 flex items-center gap-3">
        <div class="p-2 bg-blue-100 text-blue-600 rounded-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
        </div>
        <div>
            <h2 class="text-base font-semibold text-slate-800">Informasi Barang</h2>
            <p class="text-xs text-slate-500 mt-0.5">Perbaiki data barang, satuan, racikan, dan jenis fornas.</p>
        </div>
    </div>

    <!-- Form Body -->
    <div class="p-6">
        <form action="{{ route('admin.barang.update', $barang->barang_id) }}" method="POST" id="formEditBarang">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">

                <!-- Kode Barang -->
                <div>
                    <label for="kode_barang" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Kode Barang</label>
                    <input type="text" id="kode_barang" name="kode_barang" value="{{ old('kode_barang', $barang->kode_barang) }}" placeholder="Contoh: OBAT-0001"
                           class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400">
                    @error('kode_barang')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Nama Barang -->
                <div>
                    <label for="nama_barang" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Nama Barang <span class="text-red-500">*</span></label>
                    <input type="text" id="nama_barang" name="nama_barang" value="{{ old('nama_barang', $barang->nama_barang) }}" placeholder="Contoh: Paracetamol 500 mg" required
                           class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700 placeholder-slate-400">
                    @error('nama_barang')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jenis Barang -->
                <div>
                    <label for="jenis_barang_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Jenis Barang <span class="text-red-500">*</span></label>
                    <select id="jenis_barang_id" name="jenis_barang_id" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="">-- Pilih Jenis Barang --</option>
                        @foreach ($barangJenisList as $jenis)
                            <option value="{{ $jenis->barang_jenis_id }}" data-jenis="{{ strtolower(trim($jenis->nama_jenis_barang)) }}" @selected((string) old('jenis_barang_id', $barang->jenis_barang_id) === (string) $jenis->barang_jenis_id)>{{ $jenis->nama_jenis_barang }}</option>
                        @endforeach
                    </select>
                    @error('jenis_barang_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Satuan -->
                <div>
                    <label for="satuan_id" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Satuan</label>
                    <select id="satuan_id" name="satuan_id"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="">-- Pilih Satuan --</option>
                        @foreach ($satuanList as $s)
                            <option value="{{ $s->satuan_id }}" @selected((string) old('satuan_id', $barang->satuan_id) === (string) $s->satuan_id)>{{ $s->nama_satuan }}</option>
                        @endforeach
                    </select>
                    @error('satuan_id')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Racikan (hanya untuk jenis Obat) -->
                <div id="racikan-field">
                    <label for="is_racikan" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Racikan</label>
                    <select id="is_racikan" name="is_racikan"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="0" @selected((string) old('is_racikan', $barang->is_racikan) === '0')>Tidak</option>
                        <option value="1" @selected((string) old('is_racikan', $barang->is_racikan) === '1')>Ya</option>
                    </select>
                    @error('is_racikan')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fornas -->
                <div>
                    <label for="is_fornas" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Fornas</label>
                    <select id="is_fornas" name="is_fornas"
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="0" @selected((string) old('is_fornas', $barang->is_fornas) === '0')>Tidak / Non Fornas</option>
                        <option value="1" @selected((string) old('is_fornas', $barang->is_fornas) === '1')>Fornas Nasional</option>
                        <option value="2" @selected((string) old('is_fornas', $barang->is_fornas) === '2')>Fornas Rumah Sakit</option>
                    </select>
                    @error('is_fornas')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Kategori Barang -->
                <div>
                    <label for="kategori_barang" class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Kategori Barang <span class="text-red-500">*</span></label>
                    <select id="kategori_barang" name="kategori_barang" required
                            class="w-full text-sm border border-slate-200 rounded-lg px-3.5 py-2.5 bg-slate-50 focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all outline-none text-slate-700">
                        <option value="0" @selected((string) old('kategori_barang', $barang->kategori_barang) === '0')>Non Medis</option>
                        <option value="1" @selected((string) old('kategori_barang', $barang->kategori_barang) === '1')>Medis</option>
                    </select>
                    @error('kategori_barang')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <hr class="my-6 border-slate-200">

            <!-- Buttons -->
            <div class="flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('admin.barang.index') }}" class="w-full sm:w-auto px-6 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:text-slate-800 transition-colors shadow-sm text-center">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto flex items-center justify-center gap-2 px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm shadow-blue-600/20 transition-all hover:-translate-y-0.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var jenis = document.getElementById('jenis_barang_id');
        var field = document.getElementById('racikan-field');
        var racikan = document.getElementById('is_racikan');

        if (!jenis || !field || !racikan) {
            return;
        }

        function sinkronRacikan() {
            var opsi = jenis.options[jenis.selectedIndex];
            var jenisNama = opsi ? (opsi.getAttribute('data-jenis') || '').toLowerCase() : '';
            var tampil = jenisNama === 'obat';

            field.style.display = tampil ? '' : 'none';

            if (!tampil) {
                racikan.value = '0';
            }
        }

        jenis.addEventListener('change', sinkronRacikan);
        sinkronRacikan();
    });
</script>
@endpush
