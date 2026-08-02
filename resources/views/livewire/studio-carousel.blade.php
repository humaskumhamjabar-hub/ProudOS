<div data-proud-page>
    <div class="mx-auto max-w-[1600px] px-4 py-6 sm:px-6 lg:px-8">
        <header class="overflow-hidden rounded-2xl border border-zinc-200 bg-white text-zinc-950 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">
            <div class="grid gap-8 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Visual desk · Rilis 2</p>
                    <h1>Studio Carousel</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">Satu cover, slide isi sebanyak foto final. Atur crop dan naskah di sini; keluaran final berupa PNG 4:5 dalam satu ZIP.</p>
                </div>
                <div class="grid grid-cols-3 divide-x divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 text-center dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-950">
                    <div class="px-5 py-3"><strong class="block text-xl">4:5</strong><span class="text-[.65rem] uppercase tracking-wider text-zinc-500">Rasio</span></div>
                    <div class="px-5 py-3"><strong class="block text-xl">PNG</strong><span class="text-[.65rem] uppercase tracking-wider text-zinc-500">Format</span></div>
                    <div class="px-5 py-3"><strong class="block text-xl">ZIP</strong><span class="text-[.65rem] uppercase tracking-wider text-zinc-500">Paket</span></div>
                </div>
            </div>
        </header>

        @if (session('visual-tersimpan'))
            <div class="mt-4 rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">{{ session('visual-tersimpan') }}</div>
        @endif
        @error('carousel') <div class="mt-4 rounded-2xl border border-red-300 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div> @enderror

        <div class="mt-6 grid gap-6 xl:grid-cols-[270px_minmax(460px,1fr)_360px]">
            <aside class="space-y-4">
                <section class="rounded-3xl border border-zinc-200 bg-white p-4 shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                    <p class="text-[.68rem] font-semibold uppercase tracking-[.18em] text-zinc-500">Paket aktif</p>
                    <div class="mt-3 space-y-2">
                        @foreach ($paket as $item)
                            <button type="button" wire:click="pilihPaket({{ $item->id }})" wire:key="paket-visual-{{ $item->id }}" class="w-full rounded-2xl border p-3 text-left transition {{ $paketId === $item->id ? 'border-indigo-500 bg-indigo-50 shadow-sm dark:bg-indigo-950/30' : 'border-transparent hover:border-zinc-300 hover:bg-zinc-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-950' }}">
                                <span class="block text-sm font-bold leading-5">{{ $item->judul }}</span>
                                <span class="mt-1 block text-[.65rem] uppercase tracking-wider text-stone-400">{{ str($item->status)->replace('_', ' ') }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>

                @if ($paketAktif)
                    <section class="rounded-3xl border border-stone-300 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                        <label class="block text-xs font-bold">Template aktif
                            <select wire:model="templateId" class="mt-2 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @foreach ($template as $item)
                                    <option value="{{ $item->id }}" @disabled($item->status !== 'aktif')>{{ $item->nama }} · v{{ $item->versi }} {{ $item->status !== 'aktif' ? '(draf)' : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="button" wire:click="siapkanCarousel" wire:loading.attr="disabled" class="mt-3 w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">Siapkan carousel baru</button>
                        <p class="mt-2 text-[.68rem] leading-5 text-stone-500">Mengambil foto yang sudah ditandai “dipakai final” di Meja Produksi.</p>
                    </section>
                @endif
            </aside>

            <main class="min-w-0">
                @if ($renderAktif && $slideAktif)
                    <section class="rounded-[2rem] border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                        <div class="mx-auto aspect-[4/5] max-h-[760px] overflow-hidden rounded-2xl bg-[#102731] shadow-2xl" style="container-type: inline-size">
                            <div class="relative h-full w-full overflow-hidden text-white">
                                @if ($fotoPreview)
                                    <img src="{{ $fotoPreview }}" alt="Foto slide aktif" class="absolute inset-0 h-full w-full object-cover" style="transform: translate({{ $posisiX }}px, {{ $posisiY }}px) scale({{ $zoom }})">
                                @else
                                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_20%,rgba(212,168,47,.35),transparent_25%),linear-gradient(135deg,#153642,#061820)]"></div>
                                @endif
                                <div class="absolute inset-0 bg-gradient-to-b from-slate-950/5 via-slate-950/15 to-slate-950"></div>
                                <div class="absolute inset-x-[5.7%] top-[4%] flex items-center justify-between text-[1.8cqw] font-black tracking-[.14em]">
                                    <span class="flex items-center gap-[1.2cqw]"><b class="grid size-[4cqw] place-items-center rounded-full border-2 border-amber-400 text-[1.7cqw] text-amber-300">K</b>KEMENKUM JAWA BARAT</span>
                                    <span class="text-white/60">{{ str_pad((string) $slideAktif->urutan, 2, '0', STR_PAD_LEFT) }}</span>
                                </div>
                                <div class="absolute inset-x-[5.7%] bottom-[5.5%]">
                                    @if ($kickerSlide)<p class="mb-[2cqw] inline-block border-t-[.35cqw] border-amber-400 pt-[1cqw] text-[1.8cqw] font-black uppercase tracking-[.17em] text-amber-300">{{ $kickerSlide }}</p>@endif
                                    <h2 class="max-w-[92%] font-serif text-[6.5cqw] leading-[.98] tracking-[-.035em]">{{ $judulSlide }}</h2>
                                    @if ($isiSlide)<p class="mt-[2.2cqw] max-w-[88%] text-[2.35cqw] leading-[1.42] text-slate-200">{{ $isiSlide }}</p>@endif
                                </div>
                                <div class="absolute inset-x-0 bottom-0 h-[1.1%] bg-amber-400"></div>
                            </div>
                        </div>
                        <div class="mt-4 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($renderAktif->slides as $slide)
                                <button type="button" wire:click="pilihSlide({{ $slide->id }})" class="min-w-24 rounded-xl border px-3 py-2 text-left {{ $slideAktifId === $slide->id ? 'border-amber-500 bg-amber-50 text-amber-950 dark:bg-amber-950 dark:text-amber-100' : 'border-stone-300 bg-white dark:border-zinc-700 dark:bg-zinc-950' }}">
                                    <span class="block text-[.62rem] font-black uppercase tracking-wider">{{ str_pad((string) $slide->urutan, 2, '0', STR_PAD_LEFT) }} · {{ $slide->jenis }}</span>
                                    <span class="mt-1 block truncate text-xs">{{ $slide->isi_teks['judul'] ?? '' }}</span>
                                </button>
                            @endforeach
                        </div>
                    </section>
                @else
                    <section class="grid min-h-[620px] place-items-center rounded-[2rem] border border-dashed border-stone-400 bg-white/40 px-8 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div><span class="text-5xl text-amber-500">◫</span><h2 class="mt-4 font-serif text-3xl">Kanvas masih kosong</h2><p class="mt-2 max-w-md text-sm leading-6 text-stone-500">Pilih paket dengan foto final, lalu siapkan carousel baru.</p></div>
                    </section>
                @endif
            </main>

            <aside class="space-y-4">
                @if ($slideAktif)
                    <section class="rounded-3xl border border-stone-300 bg-white p-5 shadow-lg shadow-stone-950/5 dark:border-zinc-800 dark:bg-zinc-900">
                        <p class="text-[.68rem] font-black uppercase tracking-[.18em] text-amber-700 dark:text-amber-400">Komposisi slide {{ $slideAktif->urutan }}</p>
                        <div class="mt-4 space-y-4">
                            <label class="block text-xs font-bold">Label kecil
                                <input wire:model="kickerSlide" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                @error('kickerSlide') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-xs font-bold">Judul slide
                                <textarea wire:model="judulSlide" rows="3" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm leading-6 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                @error('judulSlide') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="block text-xs font-bold">Isi pendukung
                                <textarea wire:model="isiSlide" rows="4" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm leading-6 dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                @error('isiSlide') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            @foreach ([['posisiX', 'Geser horizontal', -100, 100, 1], ['posisiY', 'Geser vertikal', -100, 100, 1], ['zoom', 'Zoom foto', 1, 3, .05]] as [$model, $label, $min, $max, $step])
                                <label class="block text-xs font-bold">{{ $label }} <span class="float-right font-normal text-stone-400">{{ $$model }}</span>
                                    <input wire:model.live="{{ $model }}" type="range" min="{{ $min }}" max="{{ $max }}" step="{{ $step }}" class="mt-2 w-full accent-amber-500">
                                </label>
                            @endforeach
                            <button type="button" wire:click="simpanSlide" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700">Simpan komposisi</button>
                        </div>
                    </section>
                @endif

                @if ($renderAktif)
                    <section class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-3"><p class="text-xs font-black uppercase tracking-wider">Hasil render</p><span class="rounded-full bg-white px-2 py-1 text-[.62rem] font-bold dark:bg-zinc-950">{{ str($renderAktif->status)->title() }}</span></div>
                        @if ($renderAktif->status === 'selesai')
                            <button wire:click="unduhHasil({{ $renderAktif->id }})" class="mt-3 w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700">Unduh ZIP PNG</button>
                        @else
                            <button wire:click="renderUlang" wire:loading.attr="disabled" class="mt-3 w-full rounded-xl border border-stone-400 px-4 py-2.5 text-xs font-black hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-950">Render ulang</button>
                        @endif
                        @if ($renderAktif->pesan_gagal)<p class="mt-2 text-xs leading-5 text-red-600">{{ $renderAktif->pesan_gagal }}</p>@endif
                    </section>
                @endif
            </aside>
        </div>
    </div>
</div>
