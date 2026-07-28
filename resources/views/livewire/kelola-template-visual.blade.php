<div class="min-h-screen bg-stone-100 text-slate-900 dark:bg-zinc-950 dark:text-zinc-100">
    <main class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8">
        <header class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-amber-700 dark:text-amber-400">Visual</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Template visual</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-zinc-400">Ubah lewat versi draf, cek preview, lalu aktifkan. Versi yang pernah dipakai tetap utuh.</p>
            </div>
            <button type="button" wire:click="buatTemplateBaru" class="shrink-0 rounded-xl bg-slate-900 px-3 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 dark:bg-amber-400 dark:text-amber-950">
                Template baru
            </button>
        </header>

        @if (session('template-tersimpan'))
            <div role="status" class="mt-4 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">{{ session('template-tersimpan') }}</div>
        @endif
        @error('template') <div role="alert" class="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-medium text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div> @enderror

        <div class="mt-6 grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <section class="min-w-0" aria-labelledby="daftar-template">
                <h2 id="daftar-template" class="text-sm font-bold">Semua versi</h2>
                <div class="mt-3 flex gap-3 overflow-x-auto pb-2 lg:block lg:space-y-2 lg:overflow-visible">
                    @forelse ($templates as $item)
                        <button type="button" wire:click="pilihTemplate({{ $item->id }})" wire:key="template-{{ $item->id }}" class="min-w-64 rounded-xl border p-3 text-left transition focus:outline-none focus:ring-2 focus:ring-amber-500 {{ $templateId === $item->id ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/40' : 'border-stone-300 bg-white hover:border-stone-400 dark:border-zinc-800 dark:bg-zinc-900' }}">
                            <span class="flex items-center justify-between gap-3">
                                <strong class="truncate text-sm">{{ $item->nama }}</strong>
                                <span class="rounded-full px-2 py-1 text-[.65rem] font-bold uppercase {{ $item->status === 'aktif' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-stone-200 text-stone-700 dark:bg-zinc-800 dark:text-zinc-300' }}">{{ $item->status }}</span>
                            </span>
                            <span class="mt-2 block text-xs text-slate-500 dark:text-zinc-400">v{{ $item->versi }} · {{ str($item->format)->replace('_', ' ') }} · {{ $item->rasio }}</span>
                        </button>
                    @empty
                        <p class="min-w-64 rounded-xl border border-dashed border-stone-400 px-4 py-5 text-sm text-slate-500">Belum ada template. Buat draf pertama.</p>
                    @endforelse
                </div>
            </section>

            <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <form wire:submit="simpanDraf" class="rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold">{{ $templateAktif ? $templateAktif->nama.' v'.$templateAktif->versi : 'Draf baru' }}</h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">{{ $templateAktif?->status === 'aktif' ? 'Aktif, buat versi baru untuk mengubah.' : 'Draf dapat disunting dan dipreview.' }}</p>
                        </div>
                        @if ($templateAktif)
                            <button type="button" wire:click="buatVersiBaru({{ $templateAktif->id }})" class="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:border-zinc-700 dark:hover:bg-zinc-800">Buat versi baru</button>
                        @endif
                    </div>

                    <fieldset @disabled($templateAktif?->status === 'aktif') class="mt-5 space-y-5 disabled:opacity-60">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold sm:col-span-2">Nama
                                <input wire:model.live.debounce.300ms="nama" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950">
                                @error('nama') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="text-sm font-semibold">Format
                                <input wire:model="format" list="format-template" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950">
                                <datalist id="format-template">
                                    @foreach ($templates->pluck('format')->unique() as $formatItem)<option value="{{ $formatItem }}">@endforeach
                                </datalist>
                                @error('format') <span class="mt-1 block text-xs text-red-600">Gunakan huruf kecil, angka, atau garis bawah.</span> @enderror
                            </label>
                            <label class="text-sm font-semibold">Rasio
                                <input wire:model="rasio" inputmode="numeric" placeholder="4:5" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950">
                                @error('rasio') <span class="mt-1 block text-xs text-red-600">Gunakan format seperti 4:5.</span> @enderror
                            </label>
                        </div>

                        <div>
                            <h3 class="text-sm font-bold">Batas karakter</h3>
                            <div class="mt-3 grid gap-4 sm:grid-cols-2">
                                @foreach ([['Cover', 'coverKicker', 'Label'], ['Cover', 'coverJudul', 'Judul'], ['Cover', 'coverIsi', 'Isi'], ['Slide isi', 'isiKicker', 'Label'], ['Slide isi', 'isiJudul', 'Judul'], ['Slide isi', 'isiTeks', 'Isi']] as [$bagian, $model, $label])
                                    <label class="flex items-center justify-between gap-3 rounded-xl bg-stone-100 px-3 py-2.5 text-sm dark:bg-zinc-950">
                                        <span><strong class="block text-xs">{{ $label }}</strong><span class="text-[.68rem] text-slate-500">{{ $bagian }}</span></span>
                                        <input wire:model="{{ $model }}" type="number" min="0" inputmode="numeric" class="w-20 rounded-lg border-stone-300 bg-white text-right text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-900">
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <label class="block text-sm font-semibold">Durasi per slide <span class="font-normal text-slate-500">(opsional, untuk video)</span>
                            <div class="mt-1.5 flex items-center gap-2"><input wire:model="durasiPerSlide" type="number" min="1" max="60" inputmode="numeric" class="w-28 rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950"><span class="text-sm text-slate-500">detik</span></div>
                        </label>
                    </fieldset>

                    <div class="mt-6 grid gap-2 sm:flex sm:justify-end">
                        @if ($templateAktif?->status === 'draf')
                            <button type="button" wire:click="aktifkan({{ $templateAktif->id }})" wire:confirm="Aktifkan versi ini? Template aktif lain dengan format yang sama akan diarsipkan." class="order-2 rounded-xl border border-emerald-700 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 dark:text-emerald-300 dark:hover:bg-emerald-950">Aktifkan</button>
                        @endif
                        @if (! $templateAktif || $templateAktif->status === 'draf')
                            <button type="submit" wire:loading.attr="disabled" class="order-1 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-amber-400 dark:text-amber-950">Simpan draf</button>
                        @endif
                    </div>
                </form>

                <aside aria-label="Preview template" class="rounded-2xl border border-stone-300 bg-stone-200 p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-bold">Preview</h2><span class="text-xs text-slate-500">{{ $rasio }}</span></div>
                    <div class="mt-3 overflow-hidden rounded-xl bg-slate-900 text-white shadow-lg" style="aspect-ratio: {{ str_replace(':', '/', $rasio ?: '4:5') }}">
                        <div class="flex h-full flex-col justify-between p-[8%]">
                            <div class="flex items-center justify-between text-[.55rem] font-bold uppercase tracking-widest text-amber-300"><span>Kemenkum Jabar</span><span>01</span></div>
                            <div>
                                <p class="text-[.6rem] font-bold uppercase tracking-wider text-amber-300">Informasi layanan</p>
                                <h3 class="mt-2 text-xl font-bold leading-tight">{{ $nama ?: 'Judul template' }}</h3>
                                <p class="mt-2 text-xs leading-5 text-slate-300">Tampilan ini membantu mengecek hierarki cover sebelum versi diaktifkan.</p>
                            </div>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-2 text-xs">
                        <div><dt class="text-slate-500">Format</dt><dd class="mt-1 font-semibold">{{ str($format)->replace('_', ' ') }}</dd></div>
                        <div><dt class="text-slate-500">Judul cover</dt><dd class="mt-1 font-semibold">{{ $coverJudul }} karakter</dd></div>
                    </dl>
                </aside>
            </div>
        </div>
    </main>
</div>
