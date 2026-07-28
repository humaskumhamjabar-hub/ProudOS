# PROUD

Workspace internal Humas Kemenkum Jabar. Mengelola agenda, penugasan, produksi konten, publikasi, dan laporan dalam satu tempat.

Rancangan lengkap ada di `docs/rancangan-proud.md`. **Baca file itu sebelum menyentuh skema database, batas modul, atau alur produksi konten.** File ini hanya berisi aturan yang berlaku di setiap sesi.

## Stack

Laravel 12 · Livewire + Tailwind + Flux UI · `internachi/modular` · PostgreSQL (dev: SQLite) · Laravel Queue
(Rancangan menyebut Laravel 13; starter kit & ekosistem Flux/Volt masih mengunci 12 — naikkan saat sudah siap.)
PHP 8.4 dipakai dari `/opt/homebrew/opt/php/bin` — `php` default di PATH mesin ini masih 8.1.
Render gambar: Browsershot · Render video: FFmpeg · Backup: `spatie/laravel-backup` + `rclone`

## Struktur modul

```
app-modules/
  people/ planning/ agenda/ scheduling/ work/
  content/ visual/ publishing/ monitoring/ library/ ai/
```

Bikin modul baru: `php artisan make:module <nama>`

Di dalam tiap modul:
```
src/
  Contracts/    PUBLIK — interface yang boleh dipakai modul lain
  Events/       PUBLIK
  Actions/      PUBLIK yang sengaja diekspos
  Models/       INTERNAL — jangan disentuh dari luar modul
  Http/ Livewire/ Providers/
```

## Aturan yang tidak boleh dilanggar

1. **Tidak ada relasi Eloquent lintas modul.** Jangan `$order->customer->address`. Simpan `customer_id` sebagai kolom biasa, ambil datanya lewat contract modul tetangga.
2. **Ketergantungan antar modul dideklarasikan di `composer.json` modul itu.** Kalau belum dideklarasikan, jangan di-import.
3. **Modul lain hanya boleh menyentuh `Contracts/`, `Events/`, dan `Actions/` publik.** `Models/` internal.
4. **Apa pun yang butuh melihat banyak modul sekaligus adalah lapisan baca, bukan modul.** Beranda, Tugas Saya, Papan Kanban, Kalender, Laporan, Evaluasi PR Plan, Rekap Magang — semuanya komponen Livewire di `app/`, bukan modul.
5. **Arah ketergantungan satu arah.** Lapisan baca boleh melihat modul; modul tidak boleh melihat lapisan baca.
6. **Cek izin selalu lewat Gate/Policy**, tidak pernah `if ($user->role === 'magang')`.
7. **Yang bisa berubah disimpan sebagai data, bukan kode.** Kanal, jenis output, peran produksi, template, durasi, batas karakter — semuanya tabel, bukan enum.

## Jebakan spesifik proyek ini

- **`tugas` tidak boleh tahu jenis pekerjaannya.** Tidak ada kolom `jenis: berita|caption|foto`. Tugas menunjuk ke `subjek` polimorfik; modul lain mendaftarkan diri sebagai penyedia pekerjaan. Jenis output Humas pasti bertambah.
- **`agenda` buta terhadap sumbernya.** Tidak boleh ada `if sumber == 'pr_plan'`. Sumber hanya jejak.
- **`pr_plan_items` tidak menyimpan tanggal pasti.** Tanggal hanya ada di `agendas`. Satu sumber kebenaran.
- **`visual` tidak mengenal `agenda`.** Tanggal dan tempat disodorkan `content` sebagai teks biasa.
- **AI tidak pernah menulis ke `draf`.** Hasil AI masuk `ai_usulan`, manusia yang memutuskan. Jejak audit wajib.
- **Status kanban hidup di `paket_konten.status`.** Jangan bikin kolom `posisi_kanban` terpisah.
- **Kartu tidak bisa masuk arsip tanpa `url` terisi.**
- **Penugasan `berjam` mengunci jam, bukan hari.** Penugasan `berdeadline` tidak pernah bentrok.
- **Penugasan yang tergeser jadi `butuh_pengganti`, tidak dihapus.**

## Yang tidak dipakai

- Repository pattern, DTO berlapis, CQRS — Model + Action + Livewire sudah cukup
- Aplikasi mobile — semua lewat web
- Notifikasi WhatsApp/Telegram — notifikasi hanya di dalam sistem
- Remotion — lisensinya tidak gratis untuk instansi pemerintah
- Gerbang review internal — staf bertanggung jawab end-to-end

## Konvensi

- Nama tabel dan kolom **bahasa Indonesia** (`penugasans`, `paket_konten`, `dibaca_at`), mengikuti istilah yang dipakai tim Humas
- Nama class dan method bahasa Inggris
- Semua konfigurasi lewat `.env`, penyimpanan file lewat disk yang bisa diganti — aplikasi ini akan dipindah ke server instansi

## Sebelum commit

```bash
./vendor/bin/pest
./vendor/bin/pest --group=arch
./vendor/bin/pint
```

Arch test wajib hijau. Kalau ada batas modul yang perlu ditembus, ubah rancangannya dulu — jangan matikan testnya.
