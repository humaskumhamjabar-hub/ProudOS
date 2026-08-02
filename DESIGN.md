---
name: PROUD
description: Sistem kerja Humas yang tenang, konsisten, jelas, dan nyaman digunakan dari ponsel.
colors:
  accent: "#4f46e5"
  accent-hover: "#4338ca"
  accent-soft: "#eef2ff"
  canvas: "#fafafa"
  surface: "#ffffff"
  surface-subtle: "#f5f5f5"
  text: "#171717"
  text-muted: "#737373"
  border: "#e5e5e5"
  success: "#047857"
  success-soft: "#ecfdf5"
  warning: "#b45309"
  warning-soft: "#fffbeb"
  danger: "#dc2626"
  danger-soft: "#fef2f2"
  info: "#0369a1"
  info-soft: "#f0f9ff"
  dark-canvas: "#0a0a0a"
  dark-surface: "#171717"
  dark-surface-subtle: "#262626"
  dark-text: "#f5f5f5"
  dark-text-muted: "#a3a3a3"
  dark-border: "#404040"
typography:
  headline:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "-0.025em"
  title:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "1.125rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "-0.01em"
  body:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
    letterSpacing: "normal"
  label:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "normal"
  eyebrow:
    fontFamily: "Instrument Sans, ui-sans-serif, system-ui, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.25
    letterSpacing: "0.16em"
rounded:
  control: "8px"
  field: "12px"
  surface: "16px"
  pill: "9999px"
spacing:
  1: "4px"
  2: "8px"
  3: "12px"
  4: "16px"
  5: "20px"
  6: "24px"
  8: "32px"
  10: "40px"
components:
  button-primary:
    backgroundColor: "{colors.accent}"
    textColor: "{colors.surface}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "10px 16px"
    height: "40px"
  button-primary-hover:
    backgroundColor: "{colors.accent-hover}"
    textColor: "{colors.surface}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "10px 16px"
    height: "40px"
  button-secondary:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.label}"
    rounded: "{rounded.control}"
    padding: "10px 16px"
    height: "40px"
  field:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    typography: "{typography.body}"
    rounded: "{rounded.field}"
    padding: "10px 12px"
    height: "42px"
  page-header:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.surface}"
    padding: "20px 24px"
  content-surface:
    backgroundColor: "{colors.surface}"
    textColor: "{colors.text}"
    rounded: "{rounded.surface}"
    padding: "20px"
---

# Design System: PROUD

## Overview

**Creative North Star: "Ruang Kerja Kanwil"**

PROUD terasa seperti meja kerja bersama yang rapi di tengah ritme Humas yang cepat. Antarmuka harus tenang, terang, dan langsung menunjukkan pekerjaan berikutnya. Konsistensi adalah bagian dari fungsi: header, tombol, formulir, panel, daftar, serta umpan balik harus terasa berasal dari sistem yang sama di setiap halaman.

Pengguna banyak bekerja melalui ponsel, di kantor maupun di lapangan dengan cahaya lingkungan yang terang. Karena itu tema terang adalah tampilan utama, kontras harus kuat, kontrol harus mudah disentuh, dan struktur halaman harus tetap terbaca tanpa mengandalkan dekorasi. Tema gelap didukung sebagai padanan tonal yang setara, bukan desain yang berbeda.

Sistem ini menolak dashboard korporat yang padat ornamen, tampilan SaaS generik, istilah teknis yang tidak dipakai tim, tabel lebar yang hanya nyaman di desktop, dan alur yang menyembunyikan tindakan utama di banyak lapisan menu atau modal.

**Key Characteristics:**

- Satu keluarga visual di seluruh halaman.
- Palet restrained: netral zinc dengan indigo sebagai satu-satunya aksen interaksi utama.
- Hierarki berbasis tipografi, ruang, dan border tipis, bukan dekorasi.
- Mobile-first dengan target sentuh minimum 40px dan tata letak yang berubah secara struktural.
- Status selalu memakai warna, label teks, dan ikon bila tersedia, tidak pernah warna saja.

**The One Shell Rule.** Setiap layar terautentikasi memakai kanvas, lebar konten, padding responsif, sidebar, dan pola header yang sama. Lebar utama maksimum 1280px. Padding halaman adalah 16px di ponsel, 24px mulai `sm`, dan 32px mulai `lg`. Jarak vertikal antarseksi utama adalah 24px.

**The Mobile Workbench Rule.** Kolom bertumpuk pada ponsel, tindakan utama tetap terlihat, dan tabel yang tidak dapat dipadatkan berubah menjadi daftar berlabel. Tidak boleh ada scroll horizontal pada viewport 390px kecuali untuk tab atau kanban yang memang memiliki affordance gulir jelas.

**The Functional Motion Rule.** Transisi hanya menjelaskan perubahan keadaan selama 150–200ms dengan ease-out. Animasi dekoratif, bounce, efek parallax, dan urutan animasi saat halaman dimuat dilarang. `prefers-reduced-motion` harus dihormati.

## Colors

Palet PROUD memakai netral zinc yang sedikit hangat sebagai fondasi dan indigo sebagai satu suara interaksi. Aksen utama digunakan maksimal sekitar 10% dari layar; kelangkaannya membuat tindakan utama segera terbaca.

### Primary

- **Indigo Kerja**: khusus tombol utama, navigasi aktif, fokus, tautan tindakan, dan pilihan aktif.
- **Indigo Senyap**: latar terpilih atau informasi yang berhubungan dengan aksi utama. Jangan dipakai sebagai dekorasi bidang besar.

### Neutral

- **Kanvas Terang**: latar aplikasi dan ruang di antara permukaan.
- **Kertas Kerja**: header, panel, formulir, dan kartu pada tema terang.
- **Lapis Netral**: toolbar, filter, empty state, dan bidang pasif.
- **Tinta Utama**: judul serta informasi yang paling penting.
- **Tinta Sekunder**: metadata dan penjelasan tambahan. Jangan dipakai untuk teks kecil jika kontrasnya jatuh di bawah WCAG AA.
- **Garis Struktur**: batas panel, kontrol, pemisah, dan navigasi.

### Semantic

- **Success**: hanya keberhasilan atau status selesai.
- **Warning**: hanya perhatian, tenggat berisiko, atau keadaan yang memerlukan tindakan.
- **Danger**: hanya kegagalan, validasi salah, pembatalan, atau tindakan destruktif.
- **Info**: hanya konteks informasional yang bukan tindakan utama.

**The One Accent Rule.** Indigo adalah warna interaksi utama di semua modul. Amber, orange, emerald, sky, cyan, dan warna lain tidak boleh menjadi tema halaman atau tombol utama. Warna tersebut hanya boleh muncul sebagai status semantik.

**The Semantic Pair Rule.** Setiap status memakai pasangan latar lembut dan teks pekat. Warna penuh hanya untuk indikator kecil atau tombol semantik yang benar-benar diperlukan. Status selalu disertai kata seperti “Selesai”, “Perlu tindakan”, atau “Gagal”.

**The No Raw Color Rule.** View baru tidak boleh menambahkan hex, nama palet Tailwind baru, gradient dekoratif, atau warna modul sendiri. Gunakan token di frontmatter dan padanannya di sistem implementasi.

## Typography

**Display Font:** Instrument Sans, dengan fallback `ui-sans-serif`, `system-ui`, dan `sans-serif`.

**Body Font:** Instrument Sans, dengan fallback yang sama.

**Character:** Satu keluarga sans membuat antarmuka terasa administratif tetapi tidak kaku. Perbedaan hierarki dibangun melalui ukuran dan bobot, bukan pergantian font dekoratif.

### Hierarchy

- **Headline** (600, 30px, 1.25): satu judul halaman. Pada viewport `sm` ke atas boleh 36px. Jangan menggunakan serif atau bobot 800/900.
- **Title** (600, 18px, 1.4): judul section, panel, atau record penting.
- **Body** (400, 14px, 1.5): isi utama dan metadata. Prosa panjang dibatasi 65–75 karakter per baris.
- **Label** (600, 14px, 1.25): label formulir, tombol, tab, serta judul data pendek.
- **Eyebrow** (600, 12px, tracking 0.16em, uppercase): kategori kecil tepat di atas headline. Maksimum satu per header.

**The Sans-Only Rule.** Semua layar aplikasi memakai Instrument Sans. `font-serif`, display font, atau monospace dekoratif dilarang pada judul, tombol, label, data, dan navigasi.

**The Calm Weight Rule.** Bobot standar adalah 400, 500, dan 600. Bobot 700 hanya untuk angka atau status yang memang perlu penekanan. `font-black` dilarang.

**The Sentence Case Rule.** Tombol, tab, navigation item, dan label memakai sentence case. Uppercase hanya untuk eyebrow dan label mikro yang pendek; tracking uppercase selalu 0.16em.

## Elevation

PROUD menggunakan lapisan tonal dan border sebagai struktur utama. Permukaan berada di atas kanvas melalui latar putih, garis netral 1px, serta bayangan sangat halus. Kedalaman besar tidak boleh menjadi identitas layar.

### Shadow Vocabulary

- **Surface rest** (`0 1px 2px rgb(0 0 0 / 0.05)`): header, panel utama, dropdown, dan kartu yang benar-benar membutuhkan pemisahan dari kanvas.
- **Floating overlay** (`0 10px 25px rgb(0 0 0 / 0.10)`): hanya dropdown, popover, dialog, atau elemen sementara yang benar-benar melayang.
- **No shadow**: toolbar, baris daftar, empty state, chip, serta panel bertingkat di dalam surface.

**The Flat-by-Default Rule.** `shadow-lg`, `shadow-xl`, `shadow-2xl`, glow berwarna, dan bayangan dekoratif dilarang untuk halaman serta kartu biasa. Jika semua panel tampak melayang, hierarki telah gagal.

**The No Nested Card Rule.** Jangan membungkus kartu dengan kartu lain. Di dalam surface, gunakan divider, kelompok berjarak, atau latar subtle tanpa bayangan.

## Components

Semua komponen terasa ringkas, tenang, dan dapat diprediksi. Elemen yang menjalankan fungsi sama harus memiliki bentuk serta state yang sama di semua halaman.

### Page Header

- Satu header utama per halaman, berada di dalam shell konten.
- Permukaan putih, border 1px, radius 16px, bayangan `Surface rest`, dan padding 20px pada ponsel serta 24px pada layar lebih besar.
- Urutan konten: eyebrow opsional, headline, deskripsi singkat; tindakan utama berada di kanan pada desktop dan turun selebar konten pada ponsel.
- Ornamen lingkaran, radial gradient, garis berkilau, bidang gelap, statistik dekoratif, dan hero tinggi dilarang.

### Buttons

- **Shape:** sudut ringkas (8px), tinggi minimum 40px; 44px untuk tindakan utama yang dominan di ponsel.
- **Primary:** Indigo Kerja, teks putih, bobot 600, padding horizontal 16px. Satu primary per konteks atau panel.
- **Secondary:** permukaan putih, teks Tinta Utama, border Garis Struktur 1px.
- **Ghost:** latar transparan, teks utama atau muted, hover memakai Lapis Netral.
- **Danger:** hanya tindakan destruktif, memakai Danger sebagai teks/border atau bidang penuh saat risiko tinggi.
- **Hover / active:** perubahan warna tonal, tanpa translasi vertikal dan tanpa membesar.
- **Focus:** ring indigo 2px dengan offset 2px, terlihat pada terang dan gelap.
- **Disabled / loading:** opacity 50–60%, kursor nonaktif, label tetap menjelaskan tindakan; ukuran tidak berubah saat loading.

### Inputs / Fields

- Tinggi kontrol 42–44px, radius 12px, latar Kertas Kerja, border 1px, padding 10px 12px.
- Label berada di atas kontrol dengan jarak 6px. Bantuan dan error berada tepat di bawah kontrol dengan jarak 4px.
- Focus memakai border serta ring Indigo Kerja. Modul tidak boleh mengganti fokus menjadi amber, orange, emerald, atau warna lain.
- Error memakai border Danger, teks bantuan Danger, dan pesan spesifik. Disabled memakai Lapis Netral dan tetap terbaca.
- Textarea memakai aturan yang sama dan tinggi minimum sesuai isi. File input tidak boleh memperkenalkan gaya tombol berbeda.

### Cards / Containers

- Radius standar 16px, border 1px, latar Kertas Kerja, padding 16px pada ponsel dan 20–24px pada layar lebih besar.
- Kartu interaktif boleh memperjelas hover melalui border indigo lembut, bukan bergerak naik.
- Daftar padat menggunakan satu surface dengan divider; jangan membuat setiap baris menjadi kartu besar.
- Empty state memakai border dashed netral, teks yang menjelaskan keadaan, dan satu tindakan berikutnya bila relevan.

### Chips / Status

- Bentuk pill hanya untuk status, filter singkat, atau metadata kecil.
- Tinggi visual 24–28px, teks 12px berbobot 600, padding horizontal 10px.
- Filter terpilih memakai Indigo Senyap dengan teks indigo; status menggunakan warna semantik.
- Chip bukan tombol utama dan tidak boleh memuat kalimat panjang.

### Navigation

- Sidebar memakai kanvas netral kedua dengan border kanan 1px.
- Item aktif memakai latar Indigo Senyap, teks Indigo Kerja, dan ikon yang sama warnanya. Tidak ada stripe di sisi item.
- Grup boleh dilipat, tetapi grup yang berisi route aktif selalu terbuka.
- Mobile memakai header ringkas dan drawer sidebar. Target sentuh semua item minimum 40px.

### Feedback

- Pesan success, warning, danger, dan info memakai surface lembut, border, ikon bila ada, judul pendek, serta teks semantik pekat.
- Toast atau status inline hilang hanya setelah cukup waktu untuk dibaca dan tidak boleh menjadi satu-satunya bukti perubahan penting.
- Konfirmasi digunakan untuk tindakan destruktif atau perubahan status yang sulit dibatalkan, bukan untuk setiap simpan biasa.

**The Same Job, Same Component Rule.** Tombol simpan, tombol batal, field, tab, filter, status, dan header tidak boleh berubah bentuk atau warna antar halaman. Jika fungsi sama terlihat berbeda, implementasi harus diselaraskan dengan sistem ini.

## Do's and Don'ts

### Do:

- **Do** gunakan Indigo Kerja untuk seluruh tindakan utama, focus ring, navigasi aktif, dan pilihan aktif.
- **Do** gunakan header standar beradius 16px, border 1px, padding 20–24px, dan bayangan sangat halus pada setiap halaman.
- **Do** gunakan spacing berbasis 4px; jarak kontrol 8–12px, padding panel 16–24px, dan jarak antarseksi 24px.
- **Do** gunakan Instrument Sans pada semua UI dengan bobot 400, 500, atau 600.
- **Do** pertahankan target minimum WCAG AA, fokus keyboard yang terlihat, dan label teks untuk setiap status.
- **Do** uji setiap halaman pada lebar 390px serta desktop sebelum dianggap konsisten.
- **Do** pertahankan tindakan utama hari ini terlihat lebih dahulu dan selesaikan satu pekerjaan per layar.

### Don't:

- **Don't** membuat dashboard korporat yang padat ornamen.
- **Don't** membuat tampilan SaaS generik.
- **Don't** memakai istilah teknis yang tidak dipakai tim.
- **Don't** membuat tabel lebar yang hanya nyaman di desktop.
- **Don't** menyembunyikan tindakan utama di banyak lapisan menu atau modal.
- **Don't** menggunakan amber, orange, emerald, cyan, atau sky sebagai tema halaman atau warna primary per modul.
- **Don't** menggunakan `font-serif`, `font-black`, gradient dekoratif, gradient text, glassmorphism, blur dekoratif, atau hero metric template.
- **Don't** menggunakan `shadow-lg`, `shadow-xl`, `shadow-2xl`, glow berwarna, hover translate, bounce, atau animasi dekoratif pada kartu dan tombol biasa.
- **Don't** memakai radius lebih dari 16px untuk panel biasa; pill hanya untuk chip, status, dan kontrol segmentasi.
- **Don't** memakai side stripe lebih tebal dari 1px sebagai aksen kartu, daftar, navigation item, alert, atau callout.
- **Don't** menambahkan warna, ukuran font, radius, spacing, atau pola komponen baru langsung di view sebelum memperbarui sistem desain.
