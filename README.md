# NusaMedika

Monorepo berisi aplikasi **SIMRS/EMR (NusaMedika)** dan **service pendukung (NusaMedikaService)**.

- `NusaMedika/` — aplikasi utama (Laravel 13, PHP 8.3, PostgreSQL, Vite + Tailwind 4). Panduan lengkap: [`NusaMedika/README.md`](NusaMedika/README.md).
- `NusaMedikaService/` — dua service Express Node:
  - `BridgingService/` — bridging ke sistem eksternal (SIMRS/BPJS).
  - `StoredService/` — penyimpanan & penyajian file upload user (kategori di `storages/`).

## Struktur

```
.
├── NusaMedika/          # Aplikasi Laravel (SIMRS/EMR)
├── NusaMedikaService/   # Service Node (Bridging + Stored)
├── AGENTS.md            # Konvensi pengembangan
└── LICENSE
```

## Mulai cepat

```bash
cd NusaMedika
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan migrate --seed
```

Akses: http://localhost:8081 (login default: `superadmin`/`admin`).

Lihat [`NusaMedika/README.md`](NusaMedika/README.md) untuk instalasi manual, konfigurasi environment, dan daftar command.

## Donasi / Dukungan

Jika proyek ini bermanfaat, dukung pengembangan:

[![GitHub Sponsors](https://img.shields.io/badge/Sponsor-GitHub-ea4aaa?logo=github)](https://github.com/sponsors/meditechidn)
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-support-orange?logo=buy-me-a-coffee)](https://www.buymeacoffee.com/meditechidn)

## Lisensi

Lihat [`LICENSE`](LICENSE).