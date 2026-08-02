# Deployment PROUD

Dokumen ini adalah checklist operator. Jangan menaruh password, API key, atau kredensial server di repository.

## Kebutuhan server

- Linux, Nginx, PHP 8.4 FPM dengan ekstensi yang diminta Composer, PostgreSQL, Node.js LTS.
- Chromium/Chrome untuk carousel dan FFmpeg untuk video.
- Worker antrean dan scheduler berjalan sebagai service terpisah.
- Domain resmi dengan HTTPS. Aplikasi tidak boleh dipublikasikan lewat HTTP biasa.

## Konfigurasi produksi

1. Salin `.env.example` menjadi `.env`, lalu isi `APP_URL`, PostgreSQL, mail, dan storage.
2. Pastikan `APP_ENV=production`, `APP_DEBUG=false`, `QUEUE_CONNECTION=database`, serta `DB_QUEUE_RETRY_AFTER` dan `REDIS_QUEUE_RETRY_AFTER` lebih dari 360 detik.
3. Ganti semua password demo. Jangan memakai `password` untuk akun produksi.
4. AI tetap `AI_PROVIDER=nonaktif` sampai API key dan model disetujui operator.
5. Produksi secara default memakai `backup_s3`; isi kredensial storage luar server. Jangan set `BACKUP_DISK=local` di produksi. Environment local, development, dan testing tetap memakai `local` untuk smoke test.
6. Set `BACKUP_NOTIFICATION_EMAIL` ke alamat yang benar-benar dipantau.

## Rilis aplikasi

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan db:seed --class=DataAwalSeeder --force
php artisan optimize
php artisan queue:restart
```

Web root Nginx harus mengarah ke `public/`. Setelah TLS aktif, uji `/up` dan `/ready` sampai keduanya HTTP 200.

## Service wajib

Worker menjalankan:

```bash
php artisan queue:work --sleep=3 --tries=2 --timeout=360
```

Scheduler menjalankan setiap menit:

```bash
php artisan schedule:run
```

Gunakan systemd atau process supervisor dengan restart otomatis dan log terpisah. Jangan menjalankan worker dari sesi SSH sementara.

## Backup dan uji restore

Jadwal aplikasi membuat backup database dan file terpilih setiap hari, retensi harian 7, mingguan 4, bulanan 12. File terpilih adalah hasil render, evidence publikasi, dan Pustaka. Kredensial backup harus menunjuk ke storage di luar VPS.

Sebelum go-live:

1. Jalankan `php artisan backup:run` dan `php artisan backup:monitor`.
2. Unduh satu arsip dari storage luar server ke mesin uji yang terpisah.
3. Restore database ke database baru, bukan database produksi.
4. Pulihkan file ke direktori uji dan jalankan `php artisan migrate:status`.
5. Login, buka satu agenda, satu foto final, satu hasil render, dan satu evidence publikasi.
6. Catat tanggal, operator, nama arsip, checksum, dan hasil uji.

Go-live belum boleh dinyatakan siap sebelum latihan restore ini lulus.

## Mengaktifkan AI melalui Mexia

Superadmin mengelola konfigurasi utama dari **Referensi & Pengaturan → Pengaturan AI** atau **Settings → AI & Mexia**. API key dienkripsi di database, tidak ditampilkan kembali, dan halaman hanya tersedia bagi pengguna dengan izin `kelola_ai`.

Nilai `.env` berikut bersifat opsional dan hanya menjadi fallback awal sebelum konfigurasi pertama disimpan melalui Settings. Untuk penggunaan normal, masukkan API key melalui halaman Pengaturan AI, bukan melalui `.env`:

```dotenv
AI_PROVIDER=openai_compatible
AI_BASE_URL=https://router.mexia.me/v1
AI_API_KEY=isi_api_key_mexia_di_sini
AI_MODEL=isi_id_model_dari_mexia
AI_TIMEOUT=90
AI_PROMPT_VERSION=berita-atensi-v1
```

`AI_MODEL` harus memakai ID persis yang tersedia pada katalog Mexia dan mendukung `POST /v1/chat/completions`. Setelah mengubah `.env`, jalankan:

```bash
php artisan config:clear
php artisan config:cache
```

Verifikasi konfigurasi tanpa mencetak API key:

```bash
php artisan tinker --execute="dump(['provider' => config('ai.provider'), 'base_url' => config('ai.base_url'), 'model' => config('ai.model'), 'api_key_configured' => filled(config('ai.api_key')), 'available' => app(Modules\\Ai\\Contracts\\PenyediaAi::class)->tersedia()]);"
```

Hasil yang diharapkan: `provider` bernilai `openai_compatible`, `api_key_configured` dan `available` bernilai `true`. Selanjutnya uji dari satu tugas contoh. Hasil AI selalu disimpan sebagai usulan terpisah dan harus ditinjau manusia sebelum menjadi draf.
