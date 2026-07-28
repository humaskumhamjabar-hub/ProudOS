# Penugasan Agenda Implementation Plan

> **For Hermes:** Use subagent-driven-development skill to implement this plan task-by-task.

**Goal:** Koordinator dapat membuat, menerobos bentrok secara sadar, melihat, dan membatalkan penugasan tim langsung dari Kelola Agenda.

**Architecture:** Perluas satu Livewire component dan view yang sudah ada. Pakai `BuatPenugasan` sebagai satu-satunya jalur create agar safety rules existing tidak diduplikasi; pembatalan hanya mengubah status. Tidak ada dependency atau halaman baru.

**Tech Stack:** Laravel 12, Livewire, Pest, Tailwind/Blade, existing scheduling module.

---

### Task 1: Tambahkan perilaku assign dan cancel pada component

**Objective:** Buat vertical slice backend minimum dengan TDD.

**Files:**
- Modify: `tests/Feature/KelolaAgendaTest.php`
- Modify: `app/Livewire/KelolaAgenda.php`

**Step 1: Write failing tests**

Tambahkan test untuk:

- pengguna tanpa `kelola_penugasan` ditolak ketika memanggil operasi assign;
- koordinator memilih agenda, anggota aktif, dan peran aktif lalu membuat penugasan `berjam` dengan waktu agenda;
- agenda tanpa `selesai_at` ditolak;
- ketidakhadiran/massa akses habis tidak menghasilkan state terobos;
- bentrok pertama menghasilkan error dan state `bolehTerobos`; panggilan terobos membuat record baru serta menandai record lama `butuh_pengganti`;
- pembatalan mengubah status ke `batal`, tidak menghapus record.

Gunakan model dan action real, bukan mock.

**Step 2: Run tests to verify RED**

Run:

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pest tests/Feature/KelolaAgendaTest.php
```

Expected: FAIL karena method/state penugasan belum ada.

**Step 3: Write minimal implementation**

Di `KelolaAgenda` tambahkan state minimum:

```php
public ?int $agendaTimId = null;
public int|string $anggotaId = '';
public int|string $peranId = '';
public bool $bolehTerobos = false;
```

Tambahkan method:

- `aturTim(int $agendaId)` — authorize `kelola_penugasan`, validasi agenda, reset form, buka panel;
- `simpanPenugasan(BuatPenugasan $buat, bool $terobos = false)` — authorize, validasi IDs aktif, pastikan agenda punya selesai, panggil action dengan `untuk_type=agenda`, tangkap hanya validation message bentrok untuk mengaktifkan `bolehTerobos`, rethrow tembok/error lain;
- `terobosBentrok(BuatPenugasan $buat)` — abort kecuali `bolehTerobos`, lalu panggil jalur simpan yang sama dengan `true`;
- `batalkanPenugasan(int $id)` — authorize, scope record ke agenda panel aktif, update `status=batal`;
- reset state terobos ketika agenda/anggota/peran berubah melalui hooks `updated...` yang kecil atau pada pembukaan form.

Jangan membuat service/action baru dan jangan menyalin logika availability.

**Step 4: Run targeted tests to verify GREEN**

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pest tests/Feature/KelolaAgendaTest.php
```

Expected: all targeted tests PASS.

**Step 5: Commit**

```bash
git add app/Livewire/KelolaAgenda.php tests/Feature/KelolaAgendaTest.php
git commit -m "feat: manage agenda assignments"
```

### Task 2: Tambahkan panel Tim liputan pada UI

**Objective:** Expose behavior existing dengan UI minimum, aksesibel, dan konfirmasi untuk tindakan berisiko.

**Files:**
- Modify: `app/Livewire/KelolaAgenda.php`
- Modify: `resources/views/livewire/kelola-agenda.blade.php`
- Modify: `tests/Feature/KelolaAgendaTest.php`

**Step 1: Write failing UI test**

Tambahkan assertion render yang membuktikan:

- pengguna dengan izin melihat tombol `Atur tim`;
- panel menampilkan select anggota/peran dan daftar penugasan agenda;
- state bentrok menampilkan label + tombol `Terobos bentrok`;
- akun/peran nonaktif tidak ada di pilihan;
- tombol batalkan hadir untuk penugasan aktif.

**Step 2: Run test to verify RED**

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pest tests/Feature/KelolaAgendaTest.php
```

Expected: FAIL karena markup belum ada.

**Step 3: Write minimal UI**

Di `render()` kirim hanya data yang dibutuhkan:

- pengguna `status=aktif`, belum soft-deleted, urut nama;
- `PeranProduksi` aktif;
- penugasan untuk agenda panel aktif dengan `user` dan `peran` (jika relasi user belum ada, gunakan lookup map sederhana di read layer; jangan menambah abstraksi).

Di view:

- tampilkan `Atur tim` hanya via `@can('kelola_penugasan')`;
- panel form select native anggota/peran;
- untuk agenda tanpa waktu selesai, disable submit dan tampilkan alasan;
- warning bentrok memakai ikon/teks + warna;
- tombol `Terobos bentrok` memakai `wire:confirm`;
- daftar menampilkan nama, peran, status, serta `Belum dibaca` / `Sudah dibaca` / `Diterima`;
- tombol `Batalkan` memakai `wire:confirm`.

**Step 4: Verify targeted GREEN**

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pest tests/Feature/KelolaAgendaTest.php
```

Expected: all PASS.

**Step 5: Compile Blade**

```bash
/opt/homebrew/opt/php/bin/php artisan view:clear
/opt/homebrew/opt/php/bin/php artisan view:cache
```

Expected: both exit 0.

**Step 6: Commit**

```bash
git add app/Livewire/KelolaAgenda.php resources/views/livewire/kelola-agenda.blade.php tests/Feature/KelolaAgendaTest.php
git commit -m "feat: add agenda team panel"
```

### Task 3: Integration verification

**Objective:** Prove the feature and project remain healthy.

**Files:** No production changes unless a real failing check requires a minimal fix.

**Step 1: Run targeted tests**

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pest tests/Feature/KelolaAgendaTest.php tests/Feature/KetersediaanTest.php
```

Expected: PASS.

**Step 2: Run full suite**

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pest --compact
```

Expected: all PASS.

**Step 3: Run quality gates**

```bash
/opt/homebrew/opt/php/bin/php ./vendor/bin/pint --test
/opt/homebrew/opt/php/bin/php artisan view:cache
npm run build
git diff --check
git status --short
```

Expected: all exit 0; working tree clean after commits.

**Step 4: Manual/browser smoke**

Start local server only if none exists, login with existing local demo account without exposing credentials, then verify:

1. Kelola Agenda loads;
2. `Atur tim` opens the correct agenda panel;
3. member/role options render;
4. empty list and action controls remain readable at desktop and narrow viewport;
5. browser console has no new error.

**Step 5: Final integration commit only if needed**

Do not create an empty or squash commit. If verification required a minimal fix, commit only that fix with a focused message.
