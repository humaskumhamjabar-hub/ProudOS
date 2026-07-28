# Penugasan Tim dari Kelola Agenda

## Tujuan

Menutup gap utama Rilis 1: koordinator dapat membentuk tim liputan langsung dari agenda tanpa halaman atau alur baru yang terpisah.

## Scope

- Tambahkan panel **Tim liputan** pada setiap agenda.
- Koordinator memilih satu anggota aktif dan satu peran produksi aktif.
- Waktu penugasan `berjam` selalu mengikuti `mulai_at` dan `selesai_at` agenda.
- Simpan melalui `Modules\Scheduling\Actions\BuatPenugasan` agar aturan masa akses, ketidakhadiran, dan bentrok tetap satu sumber.
- Tampilkan anggota, peran, status penugasan, serta status dibaca/diterima.
- Izinkan pembatalan penugasan aktif dengan konfirmasi.

## Bentrok dan Terobos

Submit pertama selalu normal (`terobos: false`). Jika engine mendeteksi bentrok jam:

1. form tetap terbuka dan menampilkan peringatan yang tidak hanya mengandalkan warna;
2. tombol bahaya **Terobos bentrok** ditampilkan;
3. operator harus mengonfirmasi niat sebelum submit ulang;
4. submit ulang memakai `terobos: true`;
5. engine yang sudah ada menandai penugasan lama `butuh_pengganti` dan menghubungkannya lewat `digantikan_dari_id`.

Ketidakhadiran aktif atau masa akses habis tetap menjadi tembok dan tidak dapat diterobos.

## Otorisasi dan Validasi

- Membuka dan mengubah panel membutuhkan izin `kelola_penugasan`.
- Operasi tetap memeriksa izin di method Livewire, bukan hanya menyembunyikan tombol.
- `agendaId`, `userId`, dan `peranId` harus menunjuk data yang valid.
- Hanya pengguna berstatus aktif dan peran produksi aktif yang tersedia.
- Agenda tanpa `selesai_at` tidak dapat membuat penugasan berjam; tampilkan alasan yang jelas karena pemeriksaan tumpang tindih memerlukan rentang waktu lengkap.
- Pembatalan hanya mengubah status menjadi `batal`; data tidak dihapus.

## UI Minimum

Panel hidup di layar `Kelola Agenda` agar konteks waktu dan kegiatan tidak perlu dipilih ulang.

- Tombol **Atur tim** pada baris agenda membuka panel/form terkait.
- Form memakai select native untuk anggota dan peran.
- Daftar tim berada di panel yang sama.
- Tombol **Batalkan** memakai konfirmasi browser/native Livewire.
- Tombol **Terobos bentrok** hanya muncul setelah kegagalan bentrok dan memakai gaya bahaya.

Tidak dibuat halaman penugasan terpisah, bulk assignment, pencarian anggota, atau drag-and-drop.

## Data Flow

1. Livewire memuat agenda dan pilihan anggota/peran.
2. Operator submit anggota + peran.
3. Component membentuk data penugasan dari agenda.
4. `BuatPenugasan::handle()` melakukan seluruh safety check dan menyimpan.
5. Component memuat ulang daftar tim dan menutup form saat sukses.
6. Pada bentrok, component menyimpan state konfirmasi sementara untuk kombinasi agenda/anggota/peran; perubahan pilihan membatalkan state tersebut.

## Error Handling

- Validation error dari engine ditampilkan pada pilihan anggota.
- State terobos hanya aktif untuk error bentrok, bukan error tembok atau data invalid.
- Record yang berubah/hilang sebelum submit menghasilkan respons 404/422 standar dan tidak membuat data parsial.
- Tidak ada transaksi tambahan karena satu pemanggilan action adalah satu unit perubahan yang sudah sederhana.

## Acceptance Checks

1. Pengguna tanpa `kelola_penugasan` tidak dapat menjalankan operasi penugasan.
2. Koordinator dapat menugaskan anggota aktif ke agenda lengkap.
3. Ketidakhadiran atau masa akses habis menolak penugasan dan tidak menampilkan opsi terobos.
4. Bentrok ditolak pada submit pertama; submit terkonfirmasi berhasil dan penugasan lama menjadi `butuh_pengganti`.
5. Koordinator dapat membatalkan penugasan tanpa menghapus riwayat.
6. Daftar tim menunjukkan peran, status, dan status konfirmasi.
7. Targeted tests, full Pest suite, Pint, Blade compilation, dan Vite build lulus.

## Deliberate Omissions

- Penugasan pemrosesan hasil bertipe `berdeadline` ditunda sampai alur pembuatan tugas produksi membutuhkannya.
- Edit anggota/peran dilakukan dengan batalkan lalu buat ulang; edit in-place belum memberi nilai tambahan.
- Bulk assignment ditambahkan hanya bila volume agenda nyata membuat assign satu per satu terbukti lambat.
