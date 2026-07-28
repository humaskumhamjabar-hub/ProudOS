<div class="min-h-screen bg-stone-100 text-stone-950 dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8">
        <header class="flex items-end justify-between gap-4">
            <div><p class="text-xs font-bold uppercase tracking-[.16em] text-amber-700 dark:text-amber-400">Pedoman kerja</p><h1 class="mt-1 text-3xl font-black tracking-tight">Pustaka</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600 dark:text-zinc-400">SOP, template, dan referensi tim tersedia di satu tempat, nyaman dibaca dari ponsel.</p></div>
            @can('kelola_pustaka')<button type="button" wire:click="buat" class="shrink-0 rounded-xl bg-amber-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2">Tambah</button>@endcan
        </header>

        @if(session('sukses'))<div role="status" class="mt-5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('sukses') }}</div>@endif

        @if($formTerbuka)
            <section class="mt-5 rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                <div class="flex items-center justify-between"><h2 class="text-lg font-bold">{{ $pustakaId ? 'Perbarui pustaka' : 'Pustaka baru' }}</h2><button type="button" wire:click="tutupForm" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-stone-100 dark:hover:bg-zinc-800">Tutup</button></div>
                <form wire:submit="simpan" class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2 text-sm font-semibold">Judul<input wire:model="judul" type="text" required autofocus class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">@error('judul')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    <label class="text-sm font-semibold">Kategori<select wire:model="kategori" class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">@foreach($kategoriOptions as $nilai => $label)<option value="{{ $nilai }}">{{ $label }}</option>@endforeach</select></label>
                    <label class="text-sm font-semibold">Bentuk<select wire:model.live="tipe" class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"><option value="teks">Teks langsung</option><option value="file">Berkas</option></select></label>
                    @if($tipe === 'teks')
                        <label class="sm:col-span-2 text-sm font-semibold">Isi<textarea wire:model="isi" rows="10" required class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"></textarea>@error('isi')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    @else
                        <label class="sm:col-span-2 text-sm font-semibold">Berkas<input wire:model="berkas" type="file" class="mt-1.5 block w-full rounded-xl border border-stone-300 bg-stone-50 p-3 text-sm dark:border-zinc-700 dark:bg-zinc-950">@if($pustakaId)<span class="mt-1 block text-xs font-normal text-stone-500">Kosongkan bila berkas lama tetap digunakan.</span>@endif @error('berkas')<span class="mt-1 block text-xs text-red-600">{{ $message }}</span>@enderror</label>
                    @endif
                    <div class="sm:col-span-2 flex justify-end gap-2"><button type="button" wire:click="tutupForm" class="rounded-xl px-4 py-2.5 text-sm font-bold hover:bg-stone-100 dark:hover:bg-zinc-800">Batal</button><button type="submit" class="rounded-xl bg-amber-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-amber-600">Simpan</button></div>
                </form>
            </section>
        @endif

        <section class="mt-6 grid gap-3 sm:grid-cols-[1fr_auto]">
            <label class="sr-only" for="cari-pustaka">Cari pustaka</label><input id="cari-pustaka" wire:model.live.debounce.300ms="cari" type="search" placeholder="Cari judul atau isi…" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
            <select wire:model.live="kategoriFilter" aria-label="Filter kategori" class="rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"><option value="semua">Semua kategori</option>@foreach($kategoriOptions as $nilai => $label)<option value="{{ $nilai }}">{{ $label }}</option>@endforeach</select>
        </section>

        <section class="mt-4 space-y-3" aria-label="Daftar pustaka">
            @forelse($dokumen as $item)
                <article wire:key="pustaka-{{ $item->id }}" class="rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-900 dark:bg-amber-950 dark:text-amber-300">{{ $kategoriOptions[$item->kategori] ?? ucfirst($item->kategori) }}</span><span class="text-xs text-stone-500">Versi {{ $item->versi }} · {{ $item->updated_at->translatedFormat('j M Y') }}</span></div><h2 class="mt-2 text-lg font-bold">{{ $item->judul }}</h2></div>@can('kelola_pustaka')<button type="button" wire:click="edit({{ $item->id }})" class="rounded-lg px-3 py-2 text-sm font-bold hover:bg-stone-100 dark:hover:bg-zinc-800">Ubah</button>@endcan</div>
                    @if($item->tipe === 'teks')<p class="mt-3 whitespace-pre-line text-sm leading-6 text-stone-700 dark:text-zinc-300">{{ $item->isi }}</p>@else<button type="button" wire:click="unduh({{ $item->id }})" class="mt-4 rounded-xl border border-amber-300 bg-amber-50 px-4 py-2.5 text-sm font-bold text-amber-900 hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">Unduh berkas</button>@endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-400 px-5 py-14 text-center"><h2 class="font-bold">Pustaka belum ditemukan</h2><p class="mt-1 text-sm text-stone-500">Coba kata kunci lain atau tambahkan pedoman pertama.</p></div>
            @endforelse
        </section>
    </div>
</div>
