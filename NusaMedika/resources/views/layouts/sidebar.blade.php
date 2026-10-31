<aside class="flex flex-col w-full h-screen bg-slate-900 z-20 overflow-hidden shadow-xl border-r border-slate-800">

    <!-- Sidebar Header -->
    <div class="sidebar-header relative z-10 h-16 flex items-center px-5 border-b border-slate-800 flex-shrink-0">
        <a href="{{ route('dashboard') }}" class="app-brand flex items-center gap-3 min-w-0">
            <img src="{{ asset('logo_white.png') }}" alt="{{ config('app.name', 'SIMRS') }}" class="h-9 w-auto object-contain flex-shrink-0">
            <span class="app-name text-white font-bold text-[15px] leading-tight truncate">{{ config('app.name', 'SIMRS') }}</span>
        </a>

        {{-- Tombol tutup drawer (khusus mobile). Di desktop sidebar kolom tetap,
             jadi tombol ini disembunyikan. --}}
        <button type="button" id="sidebar-close" title="Tutup menu"
                class="lg:hidden ml-auto p-1.5 rounded-md text-slate-400 hover:text-white hover:bg-white/10 focus:outline-none transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>

    <!-- Sidebar Search -->
    <div class="search-wrap relative z-10 px-4 py-4 flex-shrink-0">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-sm text-slate-400"></i>
            </div>
            <input type="text" id="sidebar-search" class="block w-full py-2.5 pl-10 pr-3 text-sm text-white bg-slate-800/50 border border-slate-700/50 rounded-lg focus:ring-1 focus:ring-slate-500 focus:border-slate-500 placeholder-slate-400/80 transition-all outline-none backdrop-blur-sm" placeholder="Cari menu...">
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <div class="relative z-10 flex-1 overflow-y-auto px-3 pb-6 sidebar-scroll">
        <nav class="space-y-1">
            
            <!-- Dashboard Menu -->
            <a href="{{ route('dashboard') }}" class="dashboard-link flex items-center gap-3 w-full px-4 py-2.5 mb-2 text-[13px] font-bold text-white bg-white/10 backdrop-blur-md rounded-lg shadow-sm hover:bg-white/20 transition-all border border-white/5">
                <svg class="w-[18px] h-[18px] text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                <span class="dashboard-label">Dashboard</span>
            </a>

            @if(isset($moduls) && is_array($moduls) ? count($moduls) > 0 : $moduls->count() > 0)
                <!-- Section Label -->
                <div class="modul-section-label pt-5 pb-2 px-3">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Modul</p>
                </div>

                @foreach($moduls as $modul)
                    <details class="group modul-item border-b border-slate-800/60 last:border-b-0 pb-1.5 mb-1.5">
                        <summary class="modul-summary flex items-center justify-between w-full px-3 py-2.5 text-[13px] font-semibold text-slate-200 rounded-lg hover:bg-white/5 hover:text-white cursor-pointer list-none transition-colors select-none">
                            <span class="flex items-center gap-3 min-w-0">
                                @if(!empty($modul->icon_modul))
                                    <i class="{{ $modul->icon_modul }} fa-fw text-slate-400 group-hover:text-white flex-shrink-0 transition-colors text-base w-[18px] text-center fa-icon-check"></i>
                                @else
                                    <i class="fa-solid fa-square text-slate-400 group-hover:text-white flex-shrink-0 transition-colors text-base w-[18px] text-center"></i>
                                @endif
                                <span class="truncate modul-name">{{ $modul->nama_modul }}</span>
                            </span>
                            <svg class="modul-chevron w-4 h-4 text-slate-400 transition-transform duration-200 group-open:rotate-180 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </summary>

                        <div class="modul-content mt-1 ml-4 pl-4 border-l border-slate-700/50 space-y-0.5 pb-2">
                            @foreach($modul->menus as $menu)
                                <details class="group/menu menu-item border-b border-slate-800/40 last:border-b-0 pb-1 mb-1">
                                    <summary class="flex items-center justify-between w-full px-3 py-2 text-[13px] font-medium text-slate-300 hover:text-white rounded-lg hover:bg-white/5 cursor-pointer list-none transition-colors select-none">
                                        <span class="truncate menu-name">{{ $menu->nama_menu }}</span>
                                        <svg class="w-3.5 h-3.5 text-slate-500 transition-transform duration-200 group-open/menu:rotate-180 flex-shrink-0 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </summary>
                                    <div class="mt-1 ml-3 pl-3 border-l border-slate-700/30 space-y-0.5 pb-1">
                                        @php
                                            $subMenusList = $menu->subMenus ?? $menu->sub_menus ?? [];
                                        @endphp
                                        @foreach($subMenusList as $subMenu)
                                            @continue(in_array($subMenu->file_sub_menu, [
                                                'Billing/Transaksi/BillingPasien/billing_pasien',
                                                'Billing/Transaksi/Kasir/kasir',
                                            ], true))
                                            <a href="{{ \App\Providers\SubMenuRouteServiceProvider::url($subMenu->file_sub_menu) }}" class="block px-3 py-2 text-xs font-medium text-slate-400 hover:text-white hover:bg-white/5 rounded-lg transition-colors submenu-item border-b border-slate-800/20 last:border-b-0">
                                                {{ $subMenu->nama_sub_menu }}
                                            </a>
                                        @endforeach
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            @else
                <div class="px-3 py-8 text-center">
                    <p class="text-xs text-slate-400">Belum ada modul tersedia.</p>
                </div>
            @endif
        </nav>
    </div>

    <!-- Script to check for broken/missing FontAwesome icons -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(() => {
                document.querySelectorAll('.fa-icon-check').forEach(icon => {
                    const content = window.getComputedStyle(icon, '::before').content;
                    // If FA doesn't recognize the class, content is usually "none", "normal", or empty
                    if (!content || content === 'none' || content === 'normal' || content === '""') {
                        icon.className = "fa-solid fa-square text-slate-400 group-hover:text-white flex-shrink-0 transition-colors text-base w-[18px] text-center";
                    }
                });
            }, 150);
        });
    </script>

    <style>
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.15); border-radius: 20px; }
        .sidebar-scroll:hover::-webkit-scrollbar-thumb { background-color: rgba(255, 255, 255, 0.25); }

        /* Sidebar collapsed (hanya ikon modul) */
        #sidebar-wrapper.sidebar-collapsed .app-name { display: none; }
        #sidebar-wrapper.sidebar-collapsed .app-brand { justify-content: center; }
        #sidebar-wrapper.sidebar-collapsed .sidebar-header { padding-left: 0; padding-right: 0; justify-content: center; }
        #sidebar-wrapper.sidebar-collapsed .search-wrap { display: none; }
        #sidebar-wrapper.sidebar-collapsed .dashboard-label { display: none; }
        #sidebar-wrapper.sidebar-collapsed .dashboard-link { justify-content: center; padding-left: 0; padding-right: 0; }
        #sidebar-wrapper.sidebar-collapsed .modul-section-label { display: none; }
        #sidebar-wrapper.sidebar-collapsed .modul-summary { justify-content: center; padding-left: 0; padding-right: 0; }
        #sidebar-wrapper.sidebar-collapsed .modul-name { display: none; }
        #sidebar-wrapper.sidebar-collapsed .modul-chevron { display: none; }
        #sidebar-wrapper.sidebar-collapsed .modul-content { display: none; }
    </style>
</aside>
