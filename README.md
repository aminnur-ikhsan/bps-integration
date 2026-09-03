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
   | `DB_HOST` | Alamat mesin database — **bukan** `localhost`, karena aplikasi berjalan di dalam container. Kalau database berjalan di mesin yang sama, pakai `host.docker.internal`: alamat itu tetap benar walau IP mesinmu berubah karena pindah jaringan. Di server, isi IP atau hostname database sungguhan |
   | `DB_PORT` | Port yang dipublikasikan PostgreSQL di host |
   | `DB_DATABASE` | Nama database yang kamu buat di langkah 2 |
   | `DB_USERNAME`, `DB_PASSWORD` | Kredensial PostgreSQL-mu |
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

## Deploy ke server

Prasyarat di server: Docker, Traefik yang sudah berjalan pada network `proxy`, dan container PostgreSQL pada network `pgnet`.

Compose dipecah tiga. `docker-compose.yml` berisi yang berlaku di mana saja, `docker-compose.override.yml` dimuat otomatis di mesin pengembangan, dan `docker-compose.prod.yml` hanya dipakai di server. Karena itu perintah di server selalu menyebut dua berkas.

Supaya tidak perlu mengetik `-f` setiap kali, tambahkan baris ini ke `.env` server:

```
COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml
```

Setelah itu `docker compose ...` di server otomatis memakai konfigurasi produksi.

### Pertama kali

1. Buat database di container PostgreSQL:

   ```bash
   docker exec -it postgres-postgres-1 psql -U postgres -c "CREATE DATABASE bps_integration;"
   ```

2. Ambil kode dan siapkan `.env`:

   ```bash
   git clone https://github.com/aminnur-ikhsan/bps-integration.git
   cd bps-integration
   cp .env.example .env
   ```

   Selain variabel di tabel bagian local, isi juga:

   | Variabel | Isi dengan |
   |---|---|
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | Alamat https lengkap aplikasi |
   | `APP_DOMAIN` | Domain tanpa skema, dipakai Traefik untuk routing |
   | `APP_PORT` | Port yang dijatahkan untuk project ini di server |
   | `LOG_LEVEL` | `warning` |
   | `DB_HOST` | Nama container PostgreSQL |
   | `DB_PORT` | `5432` — port di dalam network, bukan port host |
   | `COMPOSE_FILE` | Seperti di atas |

   Ketik kunci dan password langsung di server. Jangan menyalin `.env` dari mesin lain.

3. Nyalakan dan isi aplikasinya:

   ```bash
   docker compose up -d --build
   docker compose --profile tools run --rm assets
   docker compose exec app composer install --no-dev --optimize-autoloader
   docker compose exec app php artisan key:generate
   docker compose exec app php artisan migrate --force
   docker compose exec app php artisan db:seed --force
   docker compose exec app php artisan config:cache
   docker compose exec app php artisan route:cache
   docker compose exec app php artisan view:cache
   ```

4. Periksa lewat IP sebelum mengarahkan domain:

   ```bash
   curl -s -o /dev/null -w '%{http_code}\n' http://<IP-server>:<APP_PORT>/login
   ```

   Harus `200`. Pastikan juga halaman error tidak menampilkan stack trace — kalau iya, `APP_DEBUG` masih hidup dan jangan lanjut sebelum diperbaiki.

5. Arahkan A record domain ke IP server. Traefik mengambil sertifikat lewat HTTP challenge, jadi DNS harus menunjuk ke server lebih dulu. Setelah itu buka domainnya, login, dan klik `Fetch Data`.

### Deploy berikutnya

```bash
git pull
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose --profile tools run --rm assets
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Setelah `config:cache`, perubahan pada `.env` tidak berpengaruh sampai cache dibangun ulang. Kalau mengubah `.env`, jalankan `php artisan config:clear` lalu `config:cache` lagi.

`APP_ENV=production` membuat Laravel menolak perintah yang merusak data seperti `migrate:fresh`, jadi perintah itu tidak bisa dijalankan di server.
