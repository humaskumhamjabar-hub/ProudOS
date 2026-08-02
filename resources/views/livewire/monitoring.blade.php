<div data-proud-page>
    <main class="mx-auto max-w-5xl px-4 py-5 sm:px-6 sm:py-8">
        <header class="flex items-end justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">Reputasi publik</p><h1 class="mt-1 text-3xl font-black tracking-tight">Monitoring</h1><p class="mt-2 max-w-xl text-sm leading-6 text-slate-600 dark:text-zinc-400">Catat temuan, tentukan PIC, lalu selesaikan tindak lanjut tanpa pindah layar.</p></div>
            <button type="button" wire:click="buat" class="shrink-0 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-400 dark:text-amber-950">Tambah</button>
        </header>

        @if (session('monitoring-sukses'))<div role="status" class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">{{ session('monitoring-sukses') }}</div>@endif

        @if ($formTerbuka)
            <section class="mt-5 rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                <div class="flex items-center justify-between gap-3"><h2 class="text-lg font-bold">{{ $temuanId ? 'Ubah temuan' : 'Temuan baru' }}</h2><button type="button" wire:click="tutupForm" class="rounded-lg px-3 py-2 text-sm font-semibold hover:bg-stone-100 dark:hover:bg-zinc-800">Tutup</button></div>
                <form wire:submit="simpan" class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-semibold">Sumber<input wire:model="sumber" type="text" placeholder="Instagram, berita daring, aduan" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950">@error('sumber')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-semibold">Tanggal<input wire:model="tanggal" type="date" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950">@error('tanggal')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="sm:col-span-2 text-sm font-semibold">Ringkasan<textarea wire:model="ringkasan" rows="4" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm leading-6 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950"></textarea>@error('ringkasan')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="sm:col-span-2 text-sm font-semibold">Tautan <span class="font-normal text-slate-500">(opsional)</span><input wire:model="url" type="url" inputmode="url" placeholder="https://…" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950">@error('url')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-semibold">Sentimen<select wire:model="sentimen" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="positif">Positif</option><option value="netral">Netral</option><option value="negatif">Negatif</option></select></label>
                    <label class="text-sm font-semibold">Status<select wire:model="statusTindakLanjut" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="baru">Baru</option><option value="diproses">Diproses</option><option value="selesai">Selesai</option></select></label>
                    <label class="sm:col-span-2 text-sm font-semibold">PIC<select wire:model="picId" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950"><option value="">Belum ditentukan</option>@foreach($pengguna as $user)<option value="{{ $user->id }}">{{ $user->nama }}</option>@endforeach</select>@error('picId')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <div class="sm:col-span-2 flex justify-end gap-2"><button type="button" wire:click="tutupForm" class="rounded-xl px-4 py-2.5 text-sm font-bold hover:bg-stone-100 dark:hover:bg-zinc-800">Batal</button><button type="submit" class="rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white hover:bg-slate-700 dark:bg-amber-400 dark:text-amber-950">Simpan</button></div>
                </form>
            </section>
        @endif

        <section class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto_auto]" aria-label="Filter monitoring">
            <input wire:model.live.debounce.300ms="cari" type="search" aria-label="Cari temuan" placeholder="Cari sumber atau ringkasan…" class="w-full rounded-xl border-stone-300 bg-white text-sm focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-900">
            <select wire:model.live="filterStatus" aria-label="Filter status" class="rounded-xl border-stone-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900"><option value="semua">Semua status</option><option value="baru">Baru</option><option value="diproses">Diproses</option><option value="selesai">Selesai</option></select>
            <select wire:model.live="filterSentimen" aria-label="Filter sentimen" class="rounded-xl border-stone-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900"><option value="semua">Semua sentimen</option><option value="positif">Positif</option><option value="netral">Netral</option><option value="negatif">Negatif</option></select>
        </section>

        <section class="mt-4 space-y-3" aria-label="Daftar temuan">
            @forelse ($temuan as $item)
                <article wire:key="temuan-{{ $item->id }}" class="rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-stone-200 px-2.5 py-1 text-xs font-bold dark:bg-zinc-800">{{ ucfirst($item->status_tindak_lanjut) }}</span><span class="text-xs font-semibold {{ $item->sentimen === 'negatif' ? 'text-red-700 dark:text-red-400' : ($item->sentimen === 'positif' ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500') }}">{{ ucfirst($item->sentimen) }}</span><span class="text-xs text-slate-500">{{ $item->tanggal->translatedFormat('j M Y') }}</span></div><h2 class="mt-2 text-sm font-bold">{{ $item->sumber }}</h2></div>
                        <button type="button" wire:click="edit({{ $item->id }})" class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-stone-100 dark:hover:bg-zinc-800">Ubah</button>
                    </div>
                    <p class="mt-3 text-sm leading-6 text-slate-700 dark:text-zinc-300">{{ $item->ringkasan }}</p>
                    <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-xs text-slate-500"><span>PIC: <strong class="text-slate-700 dark:text-zinc-300">{{ $namaPengguna[$item->pic_id] ?? 'Belum ditentukan' }}</strong></span>@if($item->url)<a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-amber-700 underline decoration-amber-300 underline-offset-2 dark:text-amber-400">Buka sumber</a>@endif</div>

                    @if ($item->tindakLanjut->isNotEmpty())
                        <details class="mt-4 rounded-xl bg-stone-100 px-3 py-2.5 dark:bg-zinc-950"><summary class="cursor-pointer text-xs font-bold">{{ $item->tindakLanjut->count() }} tindak lanjut</summary><ol class="mt-3 space-y-3">@foreach($item->tindakLanjut as $log)<li class="text-xs leading-5"><p>{{ $log->aksi }}</p><span class="text-slate-500">{{ $namaPengguna[$log->oleh_id] ?? 'Pengguna' }} · {{ $log->at->translatedFormat('j M Y, H.i') }}</span></li>@endforeach</ol></details>
                    @endif

                    @if ($item->status_tindak_lanjut !== 'selesai')
                        <div class="mt-4 grid gap-2 sm:grid-cols-[1fr_auto_auto]"><label class="sr-only" for="aksi-{{ $item->id }}">Tindak lanjut</label><input id="aksi-{{ $item->id }}" wire:model="aksi.{{ $item->id }}" type="text" placeholder="Apa yang sudah dilakukan?" class="rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950"><button type="button" wire:click="tambahTindakLanjut({{ $item->id }})" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700 dark:bg-amber-400 dark:text-amber-950">Catat</button><button type="button" wire:click="tandaiSelesai({{ $item->id }})" wire:confirm="Tandai temuan ini selesai?" class="rounded-xl border border-emerald-700 px-4 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950">Selesai</button>@error("aksi.{$item->id}")<span class="text-xs text-red-600 sm:col-span-3">{{ $message }}</span>@enderror</div>
                    @endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-400 px-5 py-14 text-center"><h2 class="font-bold">Belum ada temuan</h2><p class="mt-1 text-sm text-slate-500">Tambah temuan pertama atau ubah filter pencarian.</p></div>
            @endforelse
        </section>
    </main>
</div>
