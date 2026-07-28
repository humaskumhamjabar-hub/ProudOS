# PROUD — Rancangan Arsitektur & Skema

Workspace internal Humas Kemenkum Jabar
Dokumen kerja, **versi 2** — untuk direvisi

> Perubahan dari versi 1: review internal dihapus sebagai gerbang · magang masuk sebagai pembelajar berpembimbing, bukan jalur terpisah · papan kanban · alur harian & tombol kerjakan · status baca dan konfirmasi terima · notifikasi hanya di dalam sistem · tanpa aplikasi HP · backup & laporan.

---

## 1. Prinsip

Lima aturan yang menentukan semua keputusan di bawah. Kalau ada yang ragu di kemudian hari, kembali ke sini.

1. **Modul tidak saling menyentuh data.** Komunikasi lewat kontrak publik atau event. Tidak ada relasi Eloquent lintas modul.
2. **Apa pun yang butuh melihat banyak modul sekaligus adalah lapisan baca, bukan modul.** Beranda, Tugas Saya, Laporan, Kalender, Evaluasi PR Plan, Papan Kanban, Rekap Magang.
3. **Arah ketergantungan satu arah.** Modul boleh dilihat oleh lapisan baca; lapisan baca tidak boleh dilihat oleh modul.
4. **Yang bisa berubah disimpan sebagai data, bukan kode.** Kanal, jenis output, peran produksi, template, durasi, batas karakter.
5. **Batas ditegakkan CI, bukan niat baik.** Arch test wajib jalan sebelum merge.

---

## 2. Peta modul

```
app-modules/
├── people/        akun, peran, izin, batch magang, ketidakhadiran
├── planning/      PR Plan, jadwal konten
├── agenda/        kegiatan bertanggal — muara semua sumber
├── scheduling/    penugasan, peran produksi, pembimbing, ketersediaan
├── work/          tugas: brief, deadline, progres, konfirmasi terima
├── content/       bahan, draf, revisi, paket konten
├── visual/        template & render gambar/video
├── publishing/    kanal, tayang, link, evidence
├── monitoring/    temuan publik, tindak lanjut
├── library/       SOP, template teks, pedoman, onboarding
└── ai/            ekstraksi fakta, draf, judul, ringkasan
```

**Bukan modul** (hidup di `app/` sebagai komponen Livewire):
Beranda · Tugas Saya · Papan Kanban · Kalender · Laporan · Evaluasi PR Plan · Rekap Magang

> Modul `review` dari versi 1 **dihapus**. Tidak ada gerbang persetujuan internal — staf bertanggung jawab end-to-end. Yang tersisa adalah satu perpindahan status di papan kanban, ditambah catatan pembimbing untuk magang.

### Arah ketergantungan

```
planning ──┐
           ├──→ agenda ──→ work ──→ content ──→ visual
liputan ───┘       │         │         │           │
                   └─────────┴─────────┴───────────┴──→ scheduling
                                       │
                                       └──→ publishing ──→ monitoring

people  ← dilihat semua, tidak melihat siapa pun
library ← berdiri sendiri, hampir tanpa ketergantungan
ai      ← dipanggil content, tidak memanggil balik
```

Aturan yang ditegakkan: **tidak ada panah yang boleh berbalik.**

---

## 3. Skema database

Penulisan ringkas. `→` menandakan foreign key, `[poly]` menandakan referensi polimorfik.

### 3.1 people

```
users
  id, nama, email, password
  role_id →roles
  aktif_mulai, aktif_sampai          -- masa akses; magang wajib diisi
  batch_id →batches                  -- null untuk pegawai tetap
  status                             -- aktif | nonaktif
  timestamps, softDeletes

roles
  id, nama, slug                     -- koordinator | staf | magang | admin

permissions
  id, nama, slug                     -- kelola_template_visual, upload_publikasi, dsb

role_permission            role_id, permission_id
user_permission            user_id, permission_id     -- izin tambahan di luar peran

batches                              -- rombongan magang
  id, nama, mulai, selesai

akses_log                            -- jejak perpanjangan masa akses
  id, user_id, aktif_sampai_lama, aktif_sampai_baru, oleh_id →users, alasan

ketidakhadiran
  id, user_id
  jenis                              -- cuti | izin | sakit
  mulai, selesai, catatan
  dicatat_oleh →users
```

**Aturan penting**

- Akun magang yang lewat `aktif_sampai` **tidak dihapus** — hanya tidak bisa login dan tidak bisa ditugaskan. Seluruh jejak kerjanya tetap utuh untuk rekap batch dan penilaian.
- Cek "boleh atau tidak" **selalu lewat izin**, tidak pernah lewat `if role == 'x'`.
- `batch_id` bukan sekadar label — ini yang membuat rekap satu angkatan magang jadi satu query, bukan pekerjaan manual tiap periode.

### 3.2 planning — PR Plan

```
pr_plans
  id, nama, tema
  periode_mulai, periode_selesai
  target_jumlah_konten
  status                             -- draf | berjalan | selesai
  dibuat_oleh →users

pr_plan_items                        -- jadwal konten, belum bertanggal pasti
  id, pr_plan_id →pr_plans
  judul, catatan
  rencana_kasar                      -- "minggu ke-2 Agustus", "awal bulan"
  jenis_output_id →jenis_outputs
  kanal_tujuan                       -- json array kanal_id
  agenda_id →agendas                 -- null selama belum dijadwalkan
  status                             -- ide | dijadwalkan | diproduksi | batal
```

**Aturan penting**

- `pr_plan_items` **tidak menyimpan tanggal pasti.** Tanggal hanya ada di `agendas`. Ini mencegah kasus klasik: jadwal digeser dari layar Agenda, tapi PR Plan masih menampilkan tanggal lama.
- Item yang belum digarap **tetap di sini, tidak muncul di papan kanban.** PR Plan berfungsi sebagai antrean; papan hanya berisi yang benar-benar sedang jalan.
- **Evaluasi PR Plan tidak disimpan di sini.** Evaluasi adalah lapisan baca yang membandingkan target di tabel ini dengan realisasi di `publikasi`.
- Saat menampilkan evaluasi, pisahkan "belum tercapai" dari **"tergeser karena liputan"**. Yang pertama terbaca sebagai tim tidak becus; yang kedua terbaca sebagai beban liputan melebihi kapasitas — dan itu argumen untuk menambah orang atau menurunkan target.

### 3.3 agenda

```
agendas
  id, judul, deskripsi
  mulai_at, selesai_at               -- jam penting, bukan hanya tanggal
  lokasi
  kebutuhan_humas                    -- json: foto, video, berita, caption
  sumber_type, sumber_id  [poly]     -- pr_plan_item | jadwal_harian | manual
  status                             -- rencana | berjalan | selesai | batal
  dibuat_oleh →users
```

**Aturan penting**

- **Agenda buta terhadap asal-usulnya.** Tidak boleh ada `if sumber == 'pr_plan'` di mana pun. Sumber hanya dicatat sebagai jejak.
  Sumber Agenda pasti bertambah — permintaan mendadak pimpinan, undangan satker lain, tarikan dari kalender kantor. Dengan aturan ini, sumber baru = satu pemanggil baru, nol perubahan di modul Agenda.
- `mulai_at` wajib berisi jam, bukan hanya tanggal. Seluruh logika bentrok bergantung pada ini.

### 3.4 scheduling — penugasan, peran, pembimbing

Inti dari seluruh sistem. Ini satu-satunya otoritas yang tahu siapa sibuk kapan.

```
peran_produksi                       -- DATA, bukan enum
  id, nama, slug                     -- peliput | fotografer | videographer
                                     -- penulis_script | penulis_berita | editor
                                     -- desainer | voice_over | pendamping
  aktif

penugasans
  id, user_id →users
  tipe                               -- berjam | berdeadline
  mulai_at, selesai_at               -- diisi jika berjam
  deadline_at                        -- diisi jika berdeadline
  untuk_type, untuk_id  [poly]       -- agenda | tugas | paket_konten
  peran_id →peran_produksi
  pembimbing_id →users               -- diisi bila pelaksananya magang
  status                             -- aktif | butuh_pengganti | selesai | batal
  digantikan_dari_id →penugasans     -- jejak saat dialihkan
  dibaca_at                          -- otomatis saat halaman dibuka
  diterima_at                        -- saat tombol "terima" ditekan
  catatan
```

**Aturan ketersediaan — hanya tiga baris**

```
TEMBOK (tidak bisa dipilih sama sekali)
├── ada ketidakhadiran aktif        (cuti | izin | sakit)
└── di luar rentang masa akses      (magang kedaluwarsa)

TAHAN, TAPI BISA DITEROBOS
└── bentrok jam antar penugasan bertipe `berjam`
    dinas luar termasuk di sini, karena masuk sebagai agenda

HANYA DITAMPILKAN, TIDAK MENGHALANGI
└── jumlah penugasan `berdeadline` yang sedang dipegang
```

**Aturan penting**

- **Tidak ada sistem tier atau prioritas.** Koordinator menunjuk orang yang sedang senggang; sistem cukup menampilkan siapa yang senggang.
- Penugasan `berjam` mengunci **jamnya saja**, bukan harinya. Orang yang meliput 09:00–12:00 tetap bisa diberi pekerjaan konten di hari yang sama.
- Penugasan `berdeadline` **tidak pernah bentrok** dengan apa pun. Hanya dihitung sebagai beban dan ditampilkan.
- Saat penugasan `berjam` diterobos atau orangnya berhalangan, penugasan lama **tidak dihapus** — statusnya menjadi `butuh_pengganti` dan langsung muncul di Beranda koordinator. Override yang sunyi adalah cara tercepat membuat kegiatan tidak terliput tanpa ada yang sadar.
- Satu kegiatan liputan menghasilkan **dua penugasan**: kehadiran (`berjam`) dan pemrosesan hasil (`berdeadline`). PIC-nya boleh berbeda.
- Satu paket konten bisa punya **beberapa penugasan sekaligus** dengan peran berbeda — videographer, penulis script, editor.
- `pembimbing_id` **nempel di penugasan, bukan di orangnya.** Magang bisa dibimbing orang berbeda tiap pekerjaan, dan variasi itu justru data yang berguna: magang yang pernah dibimbing empat orang mendapat sudut pandang lebih luas daripada yang menempel ke satu orang.
- `dibaca_at` dan `diterima_at` sengaja dipisah. Yang pertama otomatis, yang kedua butuh tindakan sadar. Dengan keduanya, alasan "saya tidak tahu ada tugas" bisa diperiksa — bukan untuk mencari salah, tapi supaya koordinator tahu harus mengejar siapa **sebelum** hari-H.

### 3.5 work — tugas

```
tugas
  id, judul, brief
  deadline_at
  status                             -- baru | dikerjakan | selesai
  agenda_id →agendas                 -- null jika tugas berdiri sendiri
  subjek_type, subjek_id  [poly]     -- paket_konten | apa pun nanti
  dibuat_oleh →users

tugas_bahan
  id, tugas_id →tugas
  path, nama_asli, mime
  diunggah_oleh →users

tugas_komentar
  id, tugas_id →tugas, user_id →users, isi
```

**Aturan penting**

- **Tugas tidak tahu jenis pekerjaannya apa.** Tidak ada kolom `jenis: berita | caption | foto`.
  Jenis output Humas pasti bertambah — video, infografis, podcast, siaran pers. Kalau jenisnya tertanam di tabel inti, setiap penambahan berarti membedah modul inti. Tugas hanya menunjuk ke `subjek`; modul lain yang mendaftarkan diri sebagai penyedia pekerjaan.
- Konsekuensi praktisnya: tombol **"Kerjakan tugas ini"** hanya satu, tapi layar yang dibuka berbeda-beda. Modul `work` bertanya ke pemilik subjeknya, *"layar kerja kamu yang mana?"*. Menambah jenis pekerjaan baru = mendaftarkan satu layar, tombolnya otomatis tahu.
- PIC tidak disimpan di sini — ada di `penugasans`. Satu tugas bisa punya beberapa orang dengan peran berbeda.

### 3.6 content

```
paket_konten                         -- satu kegiatan / satu rencana = satu paket
  id
  agenda_id →agendas
  pr_plan_item_id →pr_plan_items     -- untuk konten dari PR Plan
  judul, subjudul
  status                             -- on_progress | finish_production | review | arsip
  revisi_ke                          -- bertambah tiap dikembalikan
  dibuat_oleh →users

bahan
  id, paket_konten_id →paket_konten
  tipe                               -- foto | dokumen | catatan | audio
  path, nama_asli, mime
  teks_terekstrak                    -- hasil OCR / parsing, siap dipakai AI
  status_ekstraksi                   -- menunggu | selesai | gagal
  dipakai_final                      -- true untuk foto yang masuk render
  diunggah_oleh →users
  urutan

draf
  id, paket_konten_id →paket_konten
  jenis                              -- berita | caption | judul | script
  isi, versi
  asal                               -- manusia | ai
  latihan                            -- true bila karya magang, bukan produk
  dibuat_oleh →users

catatan_pembimbing                   -- masukan, BUKAN persetujuan
  id, penugasan_id →penugasans
  isi
  oleh_id →users

ai_usulan
  id, paket_konten_id →paket_konten
  jenis                              -- fakta | berita | caption | opsi_judul | ringkasan
  isi
  status                             -- menunggu | diterima | ditolak | diedit
  ditinjau_oleh →users, ditinjau_at
  model, prompt_versi
```

**Aturan penting**

- **AI tidak pernah menulis langsung ke `draf`.** Hasil AI masuk ke `ai_usulan`, manusia yang memutuskan.
  Dua manfaatnya: ganti penyedia AI nanti tidak menyentuh modul content sama sekali, dan ada jejak audit siapa menyetujui apa. Yang kedua penting untuk instansi — kalau ada berita bermasalah, bisa ditunjukkan persis di mana manusia turun tangan.
- `teks_terekstrak` sering terlupakan. Sambutan pimpinan datang sebagai PDF, DOCX, atau foto kertas dari HP. AI tidak bisa membaca itu langsung. Tanpa langkah ekstraksi, staf akan mengetik ulang manual dan janji "sesederhana mungkin" bubar di langkah pertama.
- `catatan_pembimbing` **bukan review.** Tidak ada status disetujui atau ditolak — hanya masukan. Ini yang membedakan pendampingan magang dari gerbang persetujuan.
- `dipakai_final` menandai foto yang benar-benar masuk ke hasil akhir. Lihat bagian backup: hanya file bertanda ini yang perlu diarsipkan.

### 3.7 visual — template & render

```
template_visual
  id, nama
  format                             -- ig_carousel | tiktok_reels
  rasio                              -- 4:5 | 9:16
  versi                              -- naik tiap perubahan, tidak menimpa
  status                             -- draf | aktif | arsip
  durasi_per_slide_detik
  dibuat_oleh →users

template_layout
  id, template_visual_id →template_visual
  jenis                              -- cover | isi
  definisi                           -- referensi blade view + slot
  batas_karakter                     -- json per field, bisa disetel

template_aset
  id, template_visual_id →template_visual
  jenis                              -- overlay_video | font | logo
  path

render
  id, paket_konten_id →paket_konten
  template_visual_id →template_visual
  template_versi                     -- versi yang dipakai saat itu
  format
  status                             -- antre | proses | selesai | gagal
  path_hasil                         -- zip untuk carousel, mp4 untuk video
  dikerjakan_at, selesai_at

render_slide
  id, render_id →render
  urutan
  bahan_id →bahan
  posisi_foto                        -- json: x, y, zoom
  isi_teks                           -- json per slot
```

**Aturan penting**

- **Satu cover + N isi**, bukan "template 3 halaman". Slide 2 dan 3 bentuknya identik — hanya isinya berbeda. Kegiatan besar yang butuh 5 slide cukup ditambah fotonya, tanpa menyentuh kode.
- `template_versi` disimpan di `render`. Konten lama tetap bisa dicetak ulang persis seperti aslinya, dan pertanyaan "kok postingan bulan lalu formatnya beda?" selalu ada jawabannya.
- `posisi_foto` wajib ada. Foto dari HP bentuknya bermacam-macam; slot template tetap. Tanpa kontrol geser dan zoom, crop otomatis akan sering memotong kepala orang — dan di liputan kegiatan resmi, yang paling sering di tengah frame adalah pejabat.
- Hari, tanggal, dan tempat **tidak diketik ulang** — ditarik dari `agendas`. Modul `visual` tidak mengenal Agenda; modul `content` yang menyodorkan sebagai teks biasa.
- Ganti template butuh tiga pengaman: **versi (jangan menimpa)**, **validasi rasio/durasi/format sebelum diterima**, dan **pratinjau sebelum diaktifkan**. Salah ukuran sekali, semua render setelahnya rusak.
- Izin `kelola_template_visual` diberikan per orang, bukan lewat peran baru. Menambah peran kelima berarti mengulang hal yang sama tiap ada kemampuan baru.

### 3.8 publishing

```
kanal
  id, nama, jenis                    -- instagram | tiktok | website | x | youtube
  aktif

publikasi
  id, paket_konten_id →paket_konten
  kanal_id →kanal
  tayang_at
  url
  evidence_path                      -- tangkapan layar
  pic_id →users
  diubah_setelah_tayang              -- bool
  alasan_perubahan, diminta_oleh     -- untuk revisi dari pimpinan
```

**Aturan penting**

- Kanal adalah **data**, bukan enum. Kanal pasti bertambah.
- **Kartu tidak bisa masuk arsip tanpa `url` terisi.** Ini yang membuat "semua publikasi tercatat" berhenti jadi soal kedisiplinan dan berubah jadi soal struktur.
- Link saja tidak cukup untuk arsip. Simpan tiga hal: **link, file hasil render, dan tangkapan layar saat tayang.** Postingan bisa dihapus atau akun disetel privat; dua tahun lagi separuh link bisa jadi tidak terbuka.
- **Revisi dari pimpinan datang setelah tayang** — ini kasus yang berbeda dari review sebelum tayang dan paling sering terlewat. Catat apa yang diubah, kapan, dan atas permintaan siapa. Kalau enam bulan lagi ada yang bertanya kenapa beritanya beda dengan aslinya, jawabannya ada.

### 3.9 monitoring

```
temuan
  id, sumber, ringkasan, url
  sentimen                           -- positif | netral | negatif
  tanggal
  status_tindak_lanjut               -- baru | diproses | selesai
  pic_id →users

tindak_lanjut
  id, temuan_id →temuan
  aksi, oleh_id →users, at
```

### 3.10 library

```
pustaka
  id, judul, kategori                -- sop | template | pedoman | onboarding | referensi
  tipe                               -- file | teks
  path, isi
  versi
  dibuat_oleh →users
```

---

## 4. Papan kanban

Satu papan untuk semua pekerjaan yang sedang jalan, bisa disaring per jenis atau per orang.

```
ON PROGRESS  →  FINISH PRODUCTION  →  REVIEW  →  arsip
```

**Aturan papan**

- **Antrean bukan kolom.** Rencana yang belum digarap masih hidup di PR Plan. Kartu baru muncul begitu ada yang di-assign — jadi papan hanya berisi yang benar-benar berjalan, dan sekali lihat langsung terbaca.
- **Revisi bukan kolom.** Kartu yang dikembalikan pulang ke On Progress dengan tanda *revisi ke-2* di kartunya. Barangnya memang sedang dikerjakan, jadi tempatnya di situ. Dan angka revisi itu sendiri informatif — magang yang kartunya selalu revisi ke-3 langsung kelihatan tanpa perlu dicatat khusus.
- **Arsip bukan kolom aktif.** Kartu yang sudah tayang lewat sebulan hilang dari papan tapi tetap bisa dicari. Kalau dijadikan kolom, isinya menumpuk ratusan kartu dalam setahun.
- **Masuk arsip wajib membawa link.** Tidak ada link, kartu tidak bisa pindah.
- Magang mentok di Review — bukan karena dicegah aturan izin, tapi karena langkah berikutnya menuntut link yang hanya ada setelah upload, dan yang upload adalah PIC pegawai.

**Dua sumber, satu papan**

```
PR Plan  →  jadwal konten  ─┐
                            ├─→  ON PROGRESS → FINISH → REVIEW → arsip
Agenda   →  liputan        ─┘
```

Asalnya berbeda, jalur setelahnya sama persis. Pola yang sama dengan Agenda sebagai muara dua sumber — konsistensi ini pertanda modelnya benar.

**Catatan pembangunan**

Kanban adalah **tampilan, bukan modul.** Statusnya hidup di `paket_konten.status`. Jangan pernah ada kolom `posisi_kanban` terpisah — dua penyimpan keadaan yang sama pasti akan berbeda isi cepat atau lambat, dan itu bug yang menyebalkan dilacak.

---

## 5. Alur harian

```
1. User login
   → Beranda langsung menampilkan tugas hari ini
     penugasan berjam tampil dengan jamnya
     penugasan berdeadline tampil tanpa jam

2. Klik "Kerjakan tugas ini"
   → layar yang terbuka menyesuaikan jenis pekerjaannya
     liputan   → unggah bahan
     produksi  → editor draf / unggah hasil
     publikasi → form link

3. Unggah bahan
   foto, laporan atensi, narasi awal, sambutan pimpinan
   → ekstraksi teks berjalan di latar

4. AI membuat usulan
   fakta · draf berita · caption · opsi judul
   batas karakter template ikut dikirim sebagai instruksi ke AI,
   supaya hasilnya langsung muat di kanvas

5. Pelaksana memilih, menyunting, melengkapi
   magang: didampingi, dapat catatan pembimbing

6. Render (bila perlu visual)
   IG      → 3 PNG 4:5, dibungkus ZIP
   TikTok  → 1 MP4 9:16, durasi tetap
   keduanya berjalan di antrean latar

7. Kartu naik ke Finish Production, lalu Review

8. PIC pegawai memeriksa dan mengunggah — di luar aplikasi

9. Link dimasukkan → kartu masuk arsip
   link + file render + tangkapan layar disimpan bersama

10. Masuk laporan, evaluasi PR Plan, dan rekap magang
```

---

## 6. Notifikasi dan perangkat

**Tidak ada aplikasi HP.** Semua lewat web. Yang ingin memakai HP membuka web yang sama — tampilannya sudah menyesuaikan layar.

**Notifikasi hanya di dalam sistem.** Bukan WhatsApp, bukan Telegram.
Alasannya bukan soal pengiriman, tapi soal pertanggungjawaban: di WhatsApp tidak ada bedanya antara belum melihat dan pura-pura belum melihat. Di dalam sistem, beda itu tercatat lewat `dibaca_at` dan `diterima_at`.

Layar koordinator menampilkan paling atas: **daftar penugasan yang belum dikonfirmasi.** Itu satu-satunya hal yang menuntut tindakan hari itu.

**Syarat yang menentukan berhasil atau tidak — dan ini di luar urusan koding:**

> PROUD harus jadi satu-satunya tempat jadwal itu ada.

Kalau jadwal masih dibagikan di grup WA "biar cepat", orang tidak akan pernah membuka PROUD dan aplikasinya jadi tempat mengetik ulang. Ini penyebab kegagalan hampir semua aplikasi internal, dan pemicunya selalu sama: jalur lama dibiarkan hidup berdampingan. Yang paling menentukan justru koordinator sendiri.

Dua hal yang mempermudah: **halaman depan langsung menampilkan hari ini**, dan **jadwal harian yang bisa dicetak** untuk briefing pagi — sumbernya tetap dari sistem.

---

## 7. Laporan

> **PROUD adalah sumber kebenaran. Google Drive adalah tujuan ekspor. Satu arah.**

Link publikasi disimpan di `publikasi`. Laporan adalah **hasil cetak, bukan tempat penyimpanan** — digenerate per minggu, per bulan, per orang, per kanal, per batch magang.

Alasannya: format laporan pasti berubah. Ganti pimpinan, ganti format. Ada permintaan baru dari pusat, ganti lagi. Kalau laporan disimpan sebagai file Sheets, tiap perubahan itu kerja ulang manual dari nol. Kalau yang disimpan datanya, ganti format = ganti template ekspor.

Yang tidak boleh: mengedit rekap di Drive lalu berharap PROUD ikut tahu.

Untuk rilis pertama cukup **tombol unduh Excel/PDF**. Integrasi Drive butuh OAuth, kredensial, dan token yang harus diperbarui — beban teknis yang tidak sepadan sebelum kebutuhannya terbukti.

---

## 8. Rekap magang

Bukan modul, bukan jalur terpisah — **lapisan baca atas penugasan mereka**.

Magang dipesan seperti orang lain: masuk buku besar penugasan yang sama, dengan peran nyata, di pekerjaan nyata. Yang membuatnya "belajar" hanya dua hal yang menempel di penugasannya: **ada `pembimbing_id`**, dan **kartunya mentok di Review**.

Yang berguna ditampilkan di rekap akhir batch:

- **Ragam kegiatan, bukan hanya jumlahnya.** Magang yang sepuluh kali ikut acara seremonial saja belajarnya lebih sempit daripada yang ikut lima acara tapi bermacam jenis.
- **Sebaran peran.** Bukan "ikut 12 kegiatan", tapi "5 kali videographer, 4 kali penulis script, 3 kali fotografer".
- **Jumlah pembimbing berbeda.** Penanda luasnya sudut pandang yang didapat.
- **Karya latihan** (`draf.latihan = true`) dan catatan pembimbing sepanjang periode.

Kalau semua ini tercatat rapi, laporan akhir batch terbentuk sendiri — tidak diketik manual tiap angkatan selesai.

---

## 9. Backup

```
Harian   → dump database + file bertanda `dipakai_final`
Tujuan   → luar server (Drive / S3 / Backblaze), bukan di VPS yang sama
Retensi  → harian 7 · mingguan 4 · bulanan 12
```

Pakai `spatie/laravel-backup` untuk dump terjadwal, `rclone` untuk mendorong ke Drive.

**Empat hal yang menentukan backup berguna atau hanya perasaan aman**

1. **Backup harus keluar dari server itu.** Backup yang menumpuk di VPS yang sama percuma.
2. **Dorong dari server, jangan tarik dari lokal.** Backup yang menarik dari laptop hanya jalan kalau laptopnya menyala — dan justru saat tidak dipegang itulah server bisa mati.
3. **Kabari kalau gagal.** Backup yang diam-diam berhenti berbulan-bulan adalah skenario paling umum.
4. **Coba restore sungguhan** minimal sekali sebelum rilis.

**Soal foto**

File mentah tidak di-backup — jumlahnya bisa 50–200 per kegiatan dan biayanya tidak sepadan. Tapi **foto yang benar-benar dipakai** (`dipakai_final`) dan hasil render **ikut di-backup**: sekitar 6 file per kegiatan, bukan 200.

Alasannya: yang ada di Instagram sudah dikompres ulang dan tertimpa overlay, jadi bukan barang yang sama untuk arsip. Dan "data mentah dipegang masing-masing PIC" punya lubang — magang berganti tiap batch, dan saat batch selesai file mereka ikut pulang.

Catatan teknis: database menyimpan alamat file. Kalau file tidak ikut dipulihkan, hasil restore adalah database penuh tautan rusak — terlihat utuh padahal isinya tidak ada. Aplikasinya perlu menangani itu dengan rapi, bukan menampilkan gambar patah di mana-mana.

**Portabilitas**

Rancang supaya gampang pindah server sejak awal: semua konfigurasi lewat `.env`, penyimpanan file lewat disk yang bisa diganti, tidak ada path server yang di-hardcode. Kalau itu dijaga, pindahan cuma butuh satu sore.

---

## 10. Catatan render video

**Pembagian lapisan**

```
LAPISAN TETAP      logo masuk, logo outro, footer, transisi
                   satu file video beralpha, sekali bikin oleh desainer

LAPISAN BERUBAH    foto + teks
                   digenerate ulang tiap kegiatan
```

FFmpeg menumpuk keduanya. Tidak perlu membangun mesin animasi.

**Animasi lapisan berubah**

| Unsur | Efek |
|---|---|
| Judul | typewriter |
| Subjudul | fade-in |
| Rangkuman | fade-in / naik pelan |
| Foto | fade-in |

Typewriter **hanya untuk judul**. Rangkuman 350 karakter yang diketik satu per satu memberatkan proses dan penonton keburu scroll sebelum kalimatnya selesai.

**Yang perlu dihindari**

Remotion adalah jalan yang paling sering muncul kalau mencari cara render video programatik. Syarat lisensinya menyebut secara eksplisit bahwa entitas pemerintah dan badan sektor publik **tidak** masuk kategori gratis, dan skema otomasinya bertarif per-render dengan minimum belanja USD 100 per bulan. Untuk instansi itu bukan hanya soal angka, tapi soal mata anggaran, pembayaran valas, dan perpanjangan tahunan. FFmpeg tidak punya masalah ini.

**Lain-lain**

- Durasi **tetap**, bukan bisa diatur pengguna. Ini yang membuat beban render bisa diperkirakan dan spek server bisa dihitung.
- Karena TikTok memutar ulang otomatis, **frame terakhir harus menyambung mulus ke frame pertama.** Diselesaikan saat desain animasi, bukan setelah jadi.
- Rasio 9:16 sekaligus memberi **Instagram Reels dan Story gratis** — rasionya identik.
- Penamaan file dalam ZIP dibakukan sejak awal: `20260727_RapatKoordinasi_01.png`.

---

## 11. Tahapan rilis

**Rilis 1 — alur kerja**
people · agenda · scheduling · work · publishing · library
Beranda · Tugas Saya · Papan Kanban · Kalender · konfirmasi terima

Ini yang memberi nilai terbesar: semua kerjaan tercatat dan terpantau. Kalau bagian ini belum jalan, tim masih memakai WhatsApp dan spreadsheet.

**Rilis 2 — produksi konten**
content · ai · planning · visual (carousel PNG saja)
Evaluasi PR Plan · Rekap Magang · Laporan

**Rilis 3 — video**
visual (renderer video)

Setelah rilis 1 dan 2 berjalan beberapa bulan akan ketahuan angka nyatanya: sebulan berapa video sebenarnya dibuat. Kalau ternyata 6, mungkin tidak sepadan. Kalau 60, jelas sepadan — dan angkanya bisa dipakai untuk mengajukan anggaran.

---

## 12. Stack

| Bagian | Pilihan |
|---|---|
| Framework | Laravel 13 |
| Frontend | Livewire starter kit (Livewire + Tailwind + Flux UI) |
| Modul | `internachi/modular` |
| Database | PostgreSQL atau MySQL |
| Antrean | Laravel Queue + Horizon |
| Render gambar | Browsershot (HTML/CSS → PNG) |
| Render video | FFmpeg |
| Izin | Gate & Policy Laravel |
| Backup | `spatie/laravel-backup` + `rclone` |
| Arch test | Pest arch testing atau Deptrac |

**Catatan**

- Livewire dipilih karena komponen UI tiap modul bisa tinggal di dalam folder modulnya. Dengan Inertia + React, frontend hidup terpisah di `resources/js/` dan modularitasnya bocor separuh di sisi UI.
- Browsershot dipilih agar **pratinjau di browser dan hasil final di server memakai HTML yang sama persis**. Alternatifnya (Intervention Image) berarti dua implementasi yang harus dijaga sinkron; begitu meleset sedikit, muncul keluhan "hasilnya beda dengan yang di layar" yang menempel selamanya.
- Browsershot membutuhkan Chromium di server. Kalau nanti deploy di shared hosting, opsi ini gugur dan harus pindah ke Intervention Image.
- **Jangan pakai Repository pattern, DTO berlapis, atau CQRS.** Model + Action class + Livewire sudah cukup. Modularitasnya ada di batas antar modul, bukan di dalam modul.

---

## 13. Yang ditegakkan di CI

```php
arch('modul tidak saling menyentuh internal')
    ->expect('Modules\Content')
    ->not->toUse('Modules\Publishing\Models');

arch('tidak ada modul yang bergantung pada lapisan baca')
    ->expect('App\Livewire\Beranda')
    ->not->toBeUsedIn('Modules');

arch('agenda tidak mengenal sumbernya')
    ->expect('Modules\Agenda')
    ->not->toUse('Modules\Planning');

arch('visual tidak mengenal agenda')
    ->expect('Modules\Visual')
    ->not->toUse('Modules\Agenda');
```

Tanpa ini, dalam tiga bulan modul-modul akan saling menempel lagi — bukan karena niat buruk, tapi karena kepepet tenggat.

---

## 14. Yang masih terbuka

- Nilai batas karakter per field — disetel setelah uji coba
- Durasi per slide — disetel setelah uji coba, lalu dikunci
- Apakah magang mendapat **penilaian formal** di akhir periode (nilai/sertifikat untuk kampus).
  Dirancang: rekap kegiatan lengkap, kolom penilaian belum ada. Kalau perlu, tambahkan sekarang karena menentukan apa yang harus dicatat sepanjang periode.
- Apakah PIC kehadiran wajib sama dengan PIC pemrosesan.
  Dirancang **boleh berbeda**; kalau ternyata selalu sama, tinggal diisi otomatis dan tetap bisa diubah.
- Bentuk "jadwal harian liputan": dokumen tersendiri yang dibagikan, atau sekadar cara input kegiatan
- Apakah musik video ditambahkan di aplikasi TikTok saat posting.
  Dirancang **tanpa audio**; menanam musik dari aplikasi memunculkan urusan lisensi lagu.
- Pemindahan ke infrastruktur resmi instansi — didaftarkan dengan email instansi, dan minimal satu orang lain di kantor punya akses.
