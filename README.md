# BPS Integration

Aplikasi internal untuk mengambil data dari BPS WebAPI, menyimpannya, dan menyajikannya.

## Menjalankan di local

Prasyarat: Docker, dan PostgreSQL yang sudah berjalan di luar project ini.

1. Salin konfigurasi:

   ```bash
   cp .env.example .env
   ```

   `.env.example` hanya memuat nama variabel. Isi sendiri nilainya:

   | Variabel | Isi dengan |
   |---|---|
   | `DB_HOST` | Alamat IP mesin database — **bukan** `localhost`, karena aplikasi berjalan di dalam container |
   | `DB_PORT` | Port yang dipublikasikan PostgreSQL di host |
   | `DB_DATABASE` | Nama database yang kamu buat di langkah 2 |
   | `DB_USERNAME`, `DB_PASSWORD` | Kredensial PostgreSQL-mu |
   | `APP_URL` | `http://localhost:` diikuti nilai `APP_PORT` |
   | `APP_TIMEZONE` | `Asia/Jakarta` |
   | `ADMIN_EMAIL`, `ADMIN_PASSWORD` | Kredensial login pertama yang akan dibuat seeder |
   | `BPS_API_KEY` | Kunci dari portal webapi.bps.go.id |
   | `DOCKER_UID`, `DOCKER_GID` | Hasil `id -u` dan `id -g` |

   Jangan menulis kredensial apa pun ke `.env.example` atau README — keduanya ikut ke repository.

2. Buat database `bps_integration` secara manual di PostgreSQL.

3. Jalankan:

   ```bash
   docker compose up -d --build
   docker compose --profile tools run --rm assets
   docker compose exec app composer install
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate
   docker compose exec app php artisan db:seed
   ```

4. Buka http://localhost:8080 dan login memakai `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

## Test

```bash
docker compose exec app php artisan test
```

Test berjalan di dalam transaksi database dan di-rollback, jadi data kerja tidak terhapus.

## Membangun ulang aset

```bash
docker compose --profile tools run --rm assets
```
