<?php

use App\Livewire\Beranda;
use App\Livewire\Kalender;
use App\Livewire\KelolaAgenda;
use App\Livewire\KelolaProduksiKonten;
use App\Livewire\KelolaPrPlan;
use App\Livewire\KelolaPublikasi;
use App\Livewire\KelolaTim;
use App\Livewire\KerjakanTugas;
use App\Livewire\PapanKanban;
use App\Livewire\PusatLaporan;
use App\Livewire\StudioCarousel;
use App\Livewire\TugasSaya;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::middleware(['auth'])->group(function () {
    // Halaman depan langsung menampilkan hari ini — bukan landing page.
    Route::get('/', Beranda::class)->name('beranda');
    Route::get('tugas-saya', TugasSaya::class)->name('tugas-saya');
    Route::get('papan', PapanKanban::class)->name('papan');
    Route::get('kalender', Kalender::class)->name('kalender');
    Route::get('agenda', KelolaAgenda::class)->name('agenda.index');
    Route::get('pr-plan', KelolaPrPlan::class)->name('pr-plan.index');
    Route::get('produksi', KelolaProduksiKonten::class)->name('produksi.index');
    Route::get('publikasi', KelolaPublikasi::class)->name('publikasi.index');
    Route::get('tim', KelolaTim::class)->name('tim.index');
    Route::get('visual/carousel', StudioCarousel::class)->name('visual.carousel');
    Route::get('laporan', PusatLaporan::class)->name('laporan.index');
    Route::get('tugas/{tugasId}/kerjakan', KerjakanTugas::class)->name('tugas.kerjakan');

    Route::redirect('dashboard', '/')->name('dashboard');

    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/auth.php';
