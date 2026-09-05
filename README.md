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

Compose dipecah tiga:

| Berkas | Isi | Kapan dipakai |
|---|---|---|
| `docker-compose.yml` | service, image, volume | selalu |
| `docker-compose.override.yml` | port untuk mesin pengembangan | otomatis di local |
| `docker-compose.prod.yml` | label Traefik, network `proxy` dan `pgnet`, port server | hanya di server |

Berkas produksi **tidak** dimuat otomatis. Langkah 3 di bawah menyetel `COMPOSE_FILE` supaya `docker compose` di server selalu memakainya. Selama itu belum diset, setiap perintah `docker compose` akan memakai konfigurasi local — container tidak akan tergabung ke `pgnet` dan tidak akan punya label Traefik.

### Pertama kali

1. Buat database di container PostgreSQL:

   ```bash
   docker exec -it <nama-container-postgres> psql -U postgres -c "CREATE DATABASE bps_integration;"
   ```

2. Ambil kode:

   ```bash
   git clone https://github.com/aminnur-ikhsan/bps-integration.git
   cd bps-integration
   cp .env.example .env
   ```

3. Setel `COMPOSE_FILE` dan UID — dua baris ini yang paling sering salah kalau diketik manual, jadi biarkan shell yang mengisinya:

   ```bash
   printf 'COMPOSE_FILE=docker-compose.yml:docker-compose.prod.yml
DOCKER_UID=%s
DOCKER_GID=%s
' "$(id -u)" "$(id -g)" >> .env
   ```

   `DOCKER_UID` harus benar-benar UID milikmu, bukan angka yang diasumsikan. Container memakainya untuk menulis ke folder project; kalau tidak cocok, `composer install` dan `npm ci` gagal dengan `Permission denied`.

4. Isi sisa `.env`. Selain variabel di tabel bagian local:

   | Variabel | Isi dengan |
   |---|---|
   | `APP_ENV` | `production` |
   | `APP_DEBUG` | `false` |
   | `APP_URL` | Alamat https lengkap aplikasi |
   | `APP_DOMAIN` | Domain saja, tanpa `https://` — dipakai Traefik di dalam `Host(...)` |
   | `LOG_LEVEL` | `warning` |
   | `DB_HOST` | Nama container PostgreSQL |
   | `DB_PORT` | `5432` — port di dalam network, bukan port host |

   Ketik kunci dan password langsung di server. Jangan menyalin `.env` dari mesin lain.

5. Nyalakan dan isi aplikasinya:

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

6. Periksa dari dalam server sebelum mengarahkan domain. Container nginx tidak membuka port ke host — itu disengaja, supaya tidak ada jalur masuk yang melewati HTTPS — jadi pemeriksaannya dilakukan dari dalam:

   ```bash
   docker compose exec nginx wget -qS -O /dev/null http://localhost/login
   ```

   Harus membalas `HTTP/1.1 200 OK`, dan halaman error tidak boleh menampilkan stack trace. Kalau menampilkan, `APP_DEBUG` masih hidup — perbaiki sebelum melanjutkan.

7. Arahkan A record domain ke IP server. Traefik mengambil sertifikat lewat HTTP challenge, jadi DNS harus menunjuk ke server lebih dulu. Setelah itu buka domainnya, login, dan klik `Fetch Data`.

### Deploy berikutnya

```bash
git pull
docker compose exec app php artisan down
docker compose exec app composer install --no-dev --optimize-autoloader
docker compose --profile tools run --rm assets
docker compose exec app php artisan migrate --force
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose exec app php artisan up
```

`artisan down` dipasang supaya kode baru tidak sempat aktif sebelum migrasinya jalan. Tanpa ini ada jeda di mana kode yang sudah memakai tabel baru bisa diakses padahal tabelnya belum dibuat, dan request langsung 500.

Setelah `config:cache`, perubahan pada `.env` tidak berpengaruh sampai cache dibangun ulang: `php artisan config:clear` lalu `config:cache`.

`APP_ENV=production` membuat Laravel menolak perintah yang merusak data seperti `migrate:fresh`, jadi perintah itu tidak bisa dijalankan di server.

> **Catatan khusus rilis skema `data_bps`.** Deploy yang membawa migrasi `2026_09_03_090000`–`090002` (pemindahan `bps_domains` dan `bps_fetch_logs` ke schema `data_bps`) menghapus isi kedua tabel itu, bukan memindahkannya. Data domain bisa dipulihkan dengan menjalankan sync ulang lewat `Fetch Data`, tapi riwayat `bps_fetch_logs` hilang permanen dan tidak bisa diambil kembali. Ini bukan risiko tetap di setiap deploy — hanya berlaku untuk rilis yang membawa migrasi tersebut.

> **Sebelum migrasi yang membuat schema baru** (seperti `data_bps` di atas), pastikan dulu role database aplikasi punya hak `CREATE` di database tersebut — kalau tidak, `migrate --force` gagal saat membuat schema. Cek dengan:
>
> ```bash
> docker compose exec app php artisan tinker --execute="DB::statement('CREATE SCHEMA IF NOT EXISTS data_bps');"
> ```

### Kalau ada yang gagal

Tiga kegagalan ini yang benar-benar terjadi saat deploy pertama, beserta penyebabnya.

**`Permission denied` saat `composer install` atau `npm ci`.** Container berjalan sebagai UID yang tidak punya izin tulis ke folder project. Bandingkan keduanya:

```bash
id -u; id -g
docker compose exec app id
```

Kalau berbeda, perbaiki `DOCKER_UID` dan `DOCKER_GID` di `.env`, lalu `docker compose up -d --force-recreate` — UID ditetapkan saat container dibuat, jadi `restart` saja tidak cukup.

**`could not translate host name` saat `migrate`.** Container app tidak berada di network yang sama dengan PostgreSQL, biasanya karena `COMPOSE_FILE` belum diset sehingga konfigurasi produksi tidak terpakai:

```bash
grep COMPOSE_FILE .env
docker inspect bps_app --format '{{json .NetworkSettings.Networks}}'
```

Network app harus memuat `pgnet`. Kalau tidak, setel `COMPOSE_FILE` lalu `docker compose up -d --force-recreate`.

**Halaman tampil tanpa CSS.** Aset ditulis dengan skema `http://` di halaman `https://`, dan browser memblokirnya sebagai mixed content. Periksa:

```bash
curl -s https://<domain>/login | grep -oE 'href="[^"]*\.css"' | head -1
```

Kalau muncul `http://`, berarti Laravel belum memercayai reverse proxy. `bootstrap/app.php` harus memanggil `trustProxies`; setelah mengubahnya jalankan `docker compose restart app`, karena berkas itu di luar jangkauan `config:cache`.
