# Japati Mirror

Jalur sementara di Open API yang menyajikan snapshot data cascading Japati dari
file JSON. Bentuk request dan response sama persis dengan API Japati, jadi aplikasi
pemanggil nanti cukup berpindah base URL saat koneksi resmi ke Japati siap.

Analogi: aplikasi ini memegang fotokopi arsip Japati. Pemanggil bertanya dengan
bahasa Japati, jawabannya lembar fotokopi yang sama persis, dengan stempel tanggal
fotokopi di sampul (header `X-Japati-Mirror-Snapshot`).

Sifatnya **sementara**. Cabut atau ganti saat koneksi resmi ke Japati siap. Satu
pencarian kata `mirror` menemukan semua yang perlu dicabut (lihat bagian terakhir).

## Cara kerja singkat

- Route `japati-mirror` ada di grup `v1` di `routes/api.php`, di luar `{region}`.
  Middleware sama dengan endpoint lain: `client.log` dan `client.token` (Bearer token
  klien, didaftarkan lewat `client-access:register`).
- Controller: `app/Http/Controllers/Api/JapatiMirrorController.php`. Tanpa service
  class, tanpa tabel, tanpa token baru.
- Isi file dikirim **apa adanya (byte sama)**. Tidak di-decode lalu di-encode ulang,
  tidak dibungkus `{"data": []}`.
- Route ini **tidak** memakai `saving_body_response`, jadi isi response tidak
  tersimpan di `data_access_clients.api_request_logs`. Log hanya mencatat status,
  ukuran byte, dan header.

## Data

Data diunggah manual ke `storage/app/private/japati-mirror/<kode-tahun>/` (disk
`local`). Folder `<kode-tahun>` memakai 5 digit, mis. `20262` (data 2026 versi v2,
satu-satunya snapshot yang ada).

```
storage/app/private/japati-mirror/20262/
  satuan-kerja.json                  daftar satuan kerja
  cascading_<satuan_kerja_id>.json   satu file per satuan kerja
  manifest.json                      catatan tarikan, sumber tanggal snapshot
```

**Data memuat data pribadi pegawai** (nama dan NIP ketua tim kerja) dan repo ini
publik. Folder ini di-gitignore (`storage/app/private/.gitignore` berisi `*`). Jangan
memasukkannya ke git, docs, fixture, test, log, atau PR.

### Mengunggah di server

1. Salin seluruh isi folder `<kode-tahun>` ke `storage/app/private/japati-mirror/<kode-tahun>/`
   di folder project di server (mis. dengan `scp` atau `rsync`).
2. Pastikan user container `app` (UID dari `DOCKER_UID` di `.env`) bisa membaca:
   folder `755`, file `644`.
3. Bandingkan hash file di server dengan kolom `sha256` di `manifest.json`:

   ```bash
   sha256sum storage/app/private/japati-mirror/20262/cascading_<id>.json
   ```

## Endpoint

Base: `<base url>/api/v1/japati-mirror`. Sisa path identik dengan Japati. Semua butuh
`Authorization: Bearer <token>`; tanpa token, token salah, atau klien nonaktif
membalas `403 {"message":"Forbidden."}`.

### Daftar satuan kerja

```
GET /api/v1/japati-mirror/option/satuan-kerja?statuses[]=1&include_pemda=false
```

`200` dengan isi `satuan-kerja.json`. Query `statuses[]` dan `include_pemda`
diterima tapi diabaikan. Endpoint asli tidak memakai header tahun, jadi foldernya
tetap `20262`.

### Cascading per satuan kerja

```
GET /api/v1/japati-mirror/public-display/display-mikro/cascading?satuan_kerja_id=<id>
Default-Key-Tahun-Kinerja-public: 20262
```

| Kondisi | Balasan |
|---|---|
| `satuan_kerja_id` kosong atau tidak ada | `422 {"message":"satuan_kerja_id wajib diisi."}` |
| Header tahun tidak ada, bukan tepat 5 digit, atau foldernya tidak ada | `200 []` |
| `satuan_kerja_id` tidak ada di `satuan-kerja.json` | `404 {"message":"Satuan kerja tidak dikenal di mirror Japati."}` |
| Id valid tapi `cascading_<id>.json` belum diunggah | `404 {"message":"Data cascading mirror Japati belum diunggah."}` |
| File ada tapi tidak terbaca, kosong, atau bukan JSON valid (mis. unggahan belum selesai, izin salah) | `503 {"message":"Data mirror Japati tidak terbaca atau belum lengkap."}` |
| Selain itu | `200`, isi `cascading_<id>.json` |

Catatan:

- Id dicocokkan dengan `satuan-kerja.json` dulu sebelum jadi nama file, jadi tidak ada
  jalur path traversal. Id Dinas (4 digit) dan Biro (12 digit) dicocokkan sebagai teks.
- Japati asli membalas 302 (redirect ke alamat yang tidak bisa dihubungi) untuk id
  kosong. Itu tidak ditiru: mirror membalas 422.
- Daftar belum diunggah: `404 {"message":"Data mirror Japati belum diunggah."}`. Daftar yang
  ada tapi rusak atau kosong: `503` seperti di tabel.
- Unggah file lengkap dulu (mis. ke nama sementara lalu `mv`) supaya pemanggil tidak
  membaca file setengah jadi.

### Header penanda snapshot

Response data membawa `X-Japati-Mirror-Snapshot: <YYYY-MM-DD>`, diambil dari
`waktu` entri pertama di `manifest.json`. Kalau manifest tidak ada atau tidak
terbaca, header dilewati (bukan error). `Content-Type` selalu `application/json`.

## Contoh

```bash
curl -H "Authorization: Bearer <token>" -H "Default-Key-Tahun-Kinerja-public: 20262" \
  "http://localhost:8080/api/v1/japati-mirror/public-display/display-mikro/cascading?satuan_kerja_id=<id>"
```

Collection Postman ada di `docs/api/bps-integration.postman_collection.json`
(folder `docs` di-gitignore, jadi file itu tidak ikut di repo).

## Mencabut jalur ini

Cari kata `mirror` (`grep -rni mirror app routes README*.md`). Yang perlu dicabut:

- grup route `japati-mirror` di `routes/api.php` (dan `use` controller-nya)
- `app/Http/Controllers/Api/JapatiMirrorController.php`
- file README ini dan baris penunjuknya di `README.md`
- folder data `storage/app/private/japati-mirror/` di setiap server (tidak terlacak
  git, hapus manual)
