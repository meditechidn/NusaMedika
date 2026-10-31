# AGENTS.md — NusaMedika (monorepo)

Satu repo Git berisi dua project terpisah:

| Path | Isi | Toolchain |
|---|---|---|
| `NusaMedika/` | Aplikasi SIMRS/EMR rumah sakit (MediTechV2) | Laravel 13, PHP 8.3, Blade, Vite, Tailwind 4, PostgreSQL |
| `NusaMedikaService/` | Dua service Express Node: `BridgingService/`, `StoredService/` | Node 18, Express |

Kode, commit message, dan teks UI **menggunakan Bahasa Indonesia** — ikuti.

## WAJIB: baca `NusaMedika/AGENTS.md` dulu

`NusaMedika/AGENTS.md` (764 baris) adalah referensi domain lengkap: aturan PostgreSQL, jebakan migration, konvensi EMR/harness, pola modul & auto-route, aturan UI responsif, dan daftar fase form EMR. **Jangan mulai mengubah kode app tanpa membacanya** — isinya hard-earned (banyak bug yang sudah pernah menimpa).

Dokumen lain di `NusaMedika/`: `README.md`, `commit_rules.md` (aturan commit), `deploy/README.md` + `deploy/README-MANUAL.md`.

`NusaMedikaService/` **tidak punya dokumentasi** — kalau butuh konteks, baca kode sumbernya.

## Git

- Single repo, branch `master`, `origin` = `git@github.com:DodhyKss/NusaMedika.git`. Push = `git push origin master` (branch `master`, bukan `main`).
- `NusaMedikaService` masuk lewat **git subtree** (prefix `NusaMedikaService`). Remote `nusa-service` = `git@github.com:DodhyKss/NusaMedikaService.git` (repo GitHub lama, masih ada terpisah). Sinkron update dari repo itu: `git subtree pull --prefix=NusaMedikaService nusa-service master`.
- Pesan commit: Conventional Commits **dalam Bahasa Indonesia** (`feat(pasien): ...`, `fix(emr): ...`, `chore(repo): ...`), aturan lengkap di `NusaMedika/commit_rules.md`. Subject ≤ ±72 karakter, tanpa titik di akhir, mulai kata kerja.
- Tidak ada CI, pre-commit hook, atau branch protection — Anda yang memutuskan push.

## Menjalankan (alur Docker)

Semua perintah PHP/artisan **harus lewat container** (tidak ada PHP di host pada alur ini). `docker-compose.yml` ada di `NusaMedika/`, jadi jalankan dari folder `NusaMedika/`:

```bash
cd NusaMedika
docker compose exec app php artisan <cmd>       # artisan, migration, seeder, test
docker compose exec -T app php tools/e2e.php 22,24,26   # harness verifikasi form EMR
```

- Service compose: `web` (Apache, host **8081** → container 80), `app` (PHP-FPM, tempat artisan), `redis` (opsional, profile `redis`), `queue` (profile `redis`), `vite` (Node 22, host 5173).
- **Tidak ada service DB.** PostgreSQL jalan di luar Docker (`DB_HOST=host.docker.internal` dari container, `127.0.0.1` di host). Setup sekali: `sudo bash deploy/setup-postgres.sh`.
- `.env` wajib ada (gitignored, jangan commit). Setelah ubah `.env`: `docker compose up -d --force-recreate app web` + `config:clear`/`cache:clear` — jangan `php artisan config:cache` (konstanta SIMRS dibaca via `env()` saat runtime).
- Redis opsional: default `CACHE_STORE=file`, `SESSION_DRIVER=file`, `QUEUE_CONNECTION=sync`. Driver `database` **dilarang** (tabel `cache`/`sessions`/`jobs` tidak ada).
- Container `app`/`queue` jalan sebagai uid 1000 (`user: "1000:1000"`) supaya file hasil artisan dimiliki user host. Jangan hapus baris itu; `vendor/`, `storage/`, `bootstrap/cache` harus milik uid 1000 juga.

## Perintah harian (dari `NusaMedika/`)

```bash
composer install
composer lint      # vendor/bin/pint --test
composer format    # vendor/bin/pint  (preset laravel, pint.json)
composer test      # artisan test
npm run build      # bundle produksi (atau: docker compose exec vite npm run build)
npm run dev        # hanya di dalam container vite
```

Urutan yang biasa: `composer lint` → `composer test` → commit.

- Test = PHPUnit, hanya 4 file (`tests/Unit`, `tests/Feature`). `phpunit.xml` memaksa `sqlite :memory:`, jadi kode yang butuh DB PostgreSQL **tidak** bisa dites via PHPUnit — untuk EMR pakai harness `tools/e2e.php` (semua form harus `OK`).
- Tidak ada PHPStan/Psalm. Formatter tunggal = Pint (`pint.json` = preset `laravel`, folder `tools/` dikecualikan).

## Jebakan yang paling sering/terraform

1. **`migrate:fresh` menghapus data asli.** DB di host berisi data legacy nyata; `migrate:fresh` drop semua tabel legacy + view. Pakai `migrate` biasa, dan `migrate:status` dulu.
2. **PostgreSQL satu-satunya driver** (blok mysql di `config/database.php` sudah dihapus). Cari teks wajib `ilike` (PG `like` case-sensitive), tidak ada `TIMESTAMPDIFF`/`IFNULL`/`GROUP_CONCAT`/`DATE_FORMAT`, `insertGetId()` harus menyebut nama PK custom, `lpad()` hanya menerima `text`. Detail + contoh di `NusaMedika/AGENTS.md`.
3. **Auth plain-text** (`users.user_password`), bukan bcrypt.
4. **Auto-route dari tabel `sub_menu`.** Setelah menambah sub_menu/route: `php artisan route:clear && php artisan cache:clear`. `php artisan make:submenu {Modul}/{Menu}/{SubMenu}` membuat scaffold (bukan isi).
5. **UI responsif pakai aturan global** di `resources/css/app.css` + `resources/js/app.js` (grid 1 kolom < 640px, wrapper scroll tabel `.nusa-table-scroll`, sidebar drawer < 1024px). Jangan tulis ulang `sm:`/`md:` per view.
6. **`.env.example` hanya placeholder**; isi `.env` asli tidak boleh masuk commit.

## Penyimpanan file (WAJIB)

**Semua file yang di-upload user (gambar, dokumen, scan, dll) disimpan di `NusaMedikaService/StoredService/storages/<kategori>/` dan diakses lewat REST API StoredService — TIDAK PERNAH disimpan di `NusaMedika/`.**

- Aplikasi Laravel **dilarang** menulis file upload ke disk server. Tidak ada `hasFile()`/`->file()`/`Storage::put()`/`move_uploaded_file()` di `app/` (`FILESYSTEM_DISK=local` hanya untuk cache/framework internal Laravel). Tampilan/pengambilan gambar & dokumen harus lewat endpoint REST StoredService — jangan `<img src="/storage/...">` atau path lokal.
- Kategori folder yang sudah disiapkan di `storages/`: `pasien/`, `pegawai/`, `soap/`, `laboratorium/`, `radiologi/`. Tambah kategori baru = tambah folder + `.gitignore` (isi tiap folder di-gitignore, jadi **file milik user tidak boleh masuk commit**).
- `BridgingService` = bridging ke sistem luar (mis. SIMRS/BPJS). `StoredService` = penyimpanan & penyajian file. Keduanya service terpisah dari compose Laravel (panggil via HTTP, bukan path filesystem).
- Terverifikasi: `NusaMedikaService/StoredService/index.js` saat ini baru expose `GET /` — endpoint upload/serve **belum ada** di kode yang ter-commit. Jadi saat menambah fitur upload: buat endpoint REST-nya di StoredService, lalu panggil dari app.
- Pengecualian yang sudah ada di kode: sebagian form EMR menyimpan gambar sebagai **base64 data-URI di kolom DB** (`emr_detail`, mis. lampiran & body map nyeri) — itu data di database, bukan file di disk. Jangan dipakai alasan untuk menyimpan upload lokal.

## NusaMedikaService

```bash
cd NusaMedikaService/BridgingService   # atau StoredService
npm install
npm start                                # PORT env, default 3000
```

- Dua service terpisah, masing-masing punya `package.json`, `Dockerfile` (node:18-alpine), `index.js` entrypoint Express yang hanya expose `GET /`.
- `StoredService/storages/<kategori>/` = lokasi fisik semua file upload user (lihat section *Penyimpanan file*); tiap kategori punya `.gitignore` sendiri, hanya `.gitignore` yang ter-track.
- `BridgingService/Dockerfile` expose **3000**, `StoredService/Dockerfile` expose **3001**, tapi kode `index.js` sama-sama default ke `PORT || 3000` — set `PORT` kalau mau konsisten dengan Dockerfile.
- **`.gitignore` mengabaikan `node_modules`, tetapi `StoredService/node_modules` justru ikut ter-track di Git (616 file)** — jangan menambahkan file baru di sana, dan jangan asumsikan `npm ci` diperlukan untuk service ini.
- Tidak ada test, lint, atau CI untuk kedua service.
