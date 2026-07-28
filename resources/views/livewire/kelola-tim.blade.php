<div class="min-h-screen bg-[#eee8dd] text-[#17242b] dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto max-w-[1500px] space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <header class="relative overflow-hidden rounded-[2rem] bg-[#102831] px-6 py-8 text-white shadow-2xl shadow-slate-950/15 sm:px-9">
            <div aria-hidden="true" class="absolute -right-24 -top-32 size-96 rounded-full border border-amber-300/15"></div>
            <div aria-hidden="true" class="absolute bottom-0 right-24 h-px w-48 bg-gradient-to-r from-transparent via-amber-300/60 to-transparent"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.24em] text-amber-300">People operations</p>
                    <h1 class="mt-3 font-serif text-4xl leading-none tracking-tight sm:text-5xl">Kelola Tim</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-300">Satu meja untuk akun, peran, masa akses, batch magang, dan ketidakhadiran. Semua perubahan tetap meninggalkan jejak.</p>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-center backdrop-blur">
                        <strong class="block font-serif text-3xl">{{ $totalAktif }}</strong>
                        <span class="text-[.6rem] font-bold uppercase tracking-[.14em] text-slate-400">Anggota aktif</span>
                    </div>
                    <div class="rounded-2xl border border-amber-300/20 bg-amber-300/10 px-5 py-4 text-center backdrop-blur">
                        <strong class="block font-serif text-3xl text-amber-300">{{ $aksesAkanHabis }}</strong>
                        <span class="text-[.6rem] font-bold uppercase tracking-[.14em] text-slate-300">Akses ≤ 30 hari</span>
                    </div>
                </div>
            </div>
        </header>

        @if (session('tim-tersimpan'))
            <div class="rounded-2xl border border-emerald-300 bg-emerald-50 px-5 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('tim-tersimpan') }}</div>
        @endif

        <section class="grid gap-3 rounded-3xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-2 xl:grid-cols-[1.4fr_.8fr_.8fr_auto] xl:items-end">
            <label class="text-xs font-bold">Cari anggota
                <input wire:model.live.debounce.300ms="pencarian" type="search" placeholder="Nama atau email" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
            </label>
            <label class="text-xs font-bold">Peran
                <select wire:model.live="filterRole" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">Semua peran</option>
                    @foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->nama }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-bold">Status
                <select wire:model.live="filterStatus" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">Semua status</option>
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                </select>
            </label>
            <div class="flex gap-2">
                <button wire:click="buatBatch" class="rounded-xl border border-stone-300 px-4 py-2.5 text-xs font-black hover:bg-stone-50 dark:border-zinc-700 dark:hover:bg-zinc-800">+ Batch</button>
                <button wire:click="buatAnggota" class="rounded-xl bg-amber-400 px-5 py-2.5 text-xs font-black text-amber-950 hover:bg-amber-300">+ Anggota</button>
            </div>
        </section>

        @if ($formAnggotaTerbuka)
            <section class="rounded-[2rem] border border-stone-300 bg-white p-5 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-7">
                <div class="flex items-center justify-between gap-4">
                    <div><p class="text-[.65rem] font-black uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Profil & akses</p><h2 class="mt-1 font-serif text-3xl">{{ $anggotaId ? 'Ubah anggota' : 'Anggota baru' }}</h2></div>
                    <button wire:click="tutupForm" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-bold dark:border-zinc-700">Tutup</button>
                </div>

                <form wire:submit="simpanAnggota" class="mt-6 grid gap-4 lg:grid-cols-3">
                    <label class="text-xs font-bold">Nama lengkap
                        <input wire:model="nama" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('nama')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-xs font-bold">Email
                        <input wire:model="email" type="email" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('email')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-xs font-bold">{{ $anggotaId ? 'Password baru (opsional)' : 'Password sementara' }}
                        <input wire:model="password" type="password" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('password')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-xs font-bold">Peran
                        <select wire:model.live="roleId" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950"><option value="">Pilih peran</option>@foreach ($roles as $role)<option value="{{ $role->id }}">{{ $role->nama }}</option>@endforeach</select>
                        @error('roleId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-xs font-bold">Status
                        <select wire:model="statusAnggota" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option></select>
                    </label>
                    <label class="text-xs font-bold">Batch magang
                        <select wire:model="batchId" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950"><option value="">Bukan peserta batch</option>@foreach ($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->nama }}</option>@endforeach</select>
                        @error('batchId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-xs font-bold">Akses mulai
                        <input wire:model="aktifMulai" type="date" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('aktifMulai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    <label class="text-xs font-bold">Akses sampai
                        <input wire:model="aktifSampai" type="date" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">
                        @error('aktifSampai')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror
                    </label>
                    @if ($anggotaId)
                        <label class="text-xs font-bold">Alasan perubahan masa akses
                            <input wire:model="alasanPerpanjangan" placeholder="Tercatat di jejak akses" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">
                        </label>
                    @endif
                    <fieldset class="lg:col-span-3">
                        <legend class="text-xs font-bold">Izin tambahan di luar peran</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($permissions as $permission)
                                <label class="flex items-center gap-2 rounded-xl border border-stone-200 px-3 py-2 text-xs dark:border-zinc-800"><input wire:model="izinTambahan" type="checkbox" value="{{ $permission->id }}" class="rounded"> {{ $permission->nama }}</label>
                            @endforeach
                        </div>
                    </fieldset>
                    <div class="lg:col-span-3"><button class="rounded-xl bg-[#102831] px-6 py-3 text-sm font-black text-white hover:bg-amber-700">Simpan anggota</button></div>
                </form>
            </section>
        @endif

        @if ($formBatchTerbuka)
            <section class="rounded-[2rem] border border-stone-300 bg-white p-6 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between"><h2 class="font-serif text-2xl">Batch magang baru</h2><button wire:click="tutupForm" class="text-xs font-bold">Tutup</button></div>
                <form wire:submit="simpanBatch" class="mt-4 grid gap-4 md:grid-cols-[1.5fr_1fr_1fr_auto] md:items-end">
                    <label class="text-xs font-bold">Nama batch<input wire:model="namaBatch" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">@error('namaBatch')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-xs font-bold">Mulai<input wire:model="batchMulai" type="date" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950"></label>
                    <label class="text-xs font-bold">Selesai<input wire:model="batchSelesai" type="date" class="mt-1.5 w-full rounded-xl border-stone-300 dark:border-zinc-700 dark:bg-zinc-950">@error('batchSelesai')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <button class="rounded-xl bg-amber-400 px-5 py-2.5 text-xs font-black text-amber-950">Simpan batch</button>
                </form>
            </section>
        @endif

        @if ($formKetidakhadiranTerbuka)
            <section class="rounded-[2rem] border border-amber-300 bg-amber-50 p-6 shadow-xl dark:border-amber-800 dark:bg-amber-950">
                <div class="flex items-center justify-between"><div><p class="text-[.65rem] font-black uppercase tracking-[.18em] text-amber-700 dark:text-amber-300">Blokir ketersediaan</p><h2 class="font-serif text-2xl">Catat ketidakhadiran</h2></div><button wire:click="tutupForm" class="text-xs font-bold">Tutup</button></div>
                <form wire:submit="simpanKetidakhadiran" class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-[.7fr_1fr_1fr_1.4fr_auto] lg:items-end">
                    <label class="text-xs font-bold">Jenis<select wire:model="jenisKetidakhadiran" class="mt-1.5 w-full rounded-xl border-amber-300 bg-white dark:border-amber-800 dark:bg-zinc-950"><option value="cuti">Cuti</option><option value="izin">Izin</option><option value="sakit">Sakit</option></select></label>
                    <label class="text-xs font-bold">Mulai<input wire:model="ketidakhadiranMulai" type="date" class="mt-1.5 w-full rounded-xl border-amber-300 bg-white dark:border-amber-800 dark:bg-zinc-950"></label>
                    <label class="text-xs font-bold">Selesai<input wire:model="ketidakhadiranSelesai" type="date" class="mt-1.5 w-full rounded-xl border-amber-300 bg-white dark:border-amber-800 dark:bg-zinc-950">@error('ketidakhadiranSelesai')<span class="text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-xs font-bold">Catatan<input wire:model="catatanKetidakhadiran" class="mt-1.5 w-full rounded-xl border-amber-300 bg-white dark:border-amber-800 dark:bg-zinc-950"></label>
                    <button class="rounded-xl bg-amber-800 px-5 py-2.5 text-xs font-black text-white">Simpan</button>
                </form>
            </section>
        @endif

        <section class="grid gap-4 lg:grid-cols-2">
            @forelse ($anggota as $item)
                @php $absenAktif = $ketidakhadiran->get($item->id, collect()); @endphp
                <article wire:key="anggota-{{ $item->id }}" class="rounded-3xl border border-stone-300 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 gap-3">
                            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-[#102831] font-serif text-lg text-white">{{ $item->initials() }}</div>
                            <div class="min-w-0"><h2 class="truncate text-base font-black">{{ $item->nama }}</h2><p class="truncate text-xs text-stone-500">{{ $item->email }}</p><div class="mt-2 flex flex-wrap gap-1.5"><span class="rounded-full bg-stone-100 px-2.5 py-1 text-[.62rem] font-black uppercase tracking-[.1em] dark:bg-zinc-800">{{ $item->role?->nama ?? 'Tanpa peran' }}</span><span class="rounded-full px-2.5 py-1 text-[.62rem] font-black uppercase {{ $item->status === 'aktif' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' }}">{{ $item->status }}</span></div></div>
                        </div>
                        <button wire:click="editAnggota({{ $item->id }})" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-black hover:bg-stone-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Ubah</button>
                    </div>
                    <div class="mt-5 grid grid-cols-2 gap-3 border-t border-stone-100 pt-4 text-xs dark:border-zinc-800">
                        <div><span class="block text-[.62rem] font-black uppercase tracking-[.12em] text-stone-400">Masa akses</span><strong class="mt-1 block">{{ $item->aktif_mulai?->translatedFormat('j M Y') ?? 'Tidak dibatasi' }}{{ $item->aktif_sampai ? ' — '.$item->aktif_sampai->translatedFormat('j M Y') : '' }}</strong></div>
                        <div><span class="block text-[.62rem] font-black uppercase tracking-[.12em] text-stone-400">Batch</span><strong class="mt-1 block">{{ $item->batch?->nama ?? '—' }}</strong></div>
                    </div>
                    @if ($item->izinTambahan->isNotEmpty())<p class="mt-3 text-[.68rem] leading-5 text-stone-500">Izin tambahan: {{ $item->izinTambahan->pluck('nama')->join(', ') }}</p>@endif
                    @if ($absenAktif->isNotEmpty())<div class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-950 dark:text-amber-200">{{ $absenAktif->map(fn ($absen) => ucfirst($absen->jenis).' '.$absen->mulai->translatedFormat('j M').'–'.$absen->selesai->translatedFormat('j M'))->join(' · ') }}</div>@endif
                    <div class="mt-4"><button wire:click="catatKetidakhadiran({{ $item->id }})" class="text-xs font-black text-amber-800 hover:text-amber-600 dark:text-amber-300">+ Catat cuti / izin / sakit</button></div>
                </article>
            @empty
                <div class="col-span-full rounded-3xl border border-dashed border-stone-400 px-6 py-20 text-center text-sm text-stone-400">Tidak ada anggota yang cocok dengan filter.</div>
            @endforelse
        </section>
    </div>
</div>
