<div data-proud-page>
    <main class="mx-auto max-w-[100rem] px-4 py-5 sm:px-6 sm:py-8">
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
                            @if ($item->format === 'ig_carousel')<span class="mt-1 block text-[.68rem] font-semibold {{ $item->aset->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() === 3 ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $item->aset->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() }}/3 background</span>@endif
                            @if ($item->format === 'video_vertikal')<span class="mt-1 block text-[.68rem] font-semibold {{ $item->layouts->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3'])->count() === 3 ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $item->layouts->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3'])->count() }}/3 scene · {{ $item->aset->filter(fn ($aset) => str_starts_with($aset->jenis, 'video_scene_'))->count() }} PNG</span>@endif
                        </button>
                    @empty
                        <p class="min-w-64 rounded-xl border border-dashed border-stone-400 px-4 py-5 text-sm text-slate-500">Belum ada template. Buat draf pertama.</p>
                    @endforelse
                </div>
            </section>

            <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <form wire:submit="simpanDraf" class="rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6 {{ $format === 'video_vertikal' ? 'xl:col-span-2' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold">{{ $templateAktif ? $templateAktif->nama.' v'.$templateAktif->versi : 'Draf baru' }}</h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-zinc-400">{{ $templateAktif?->status === 'draf' || ! $templateAktif ? 'Draf dapat disunting dan dipreview.' : ($templateAktif?->status === 'aktif' ? 'Aktif, buat versi baru untuk mengubah.' : 'Versi arsip hanya dapat dilihat. Buat versi baru untuk mengubah.') }}</p>
                        </div>
                        @if ($templateAktif)
                            <div class="flex shrink-0 flex-wrap justify-end gap-2">
                                <button type="button" wire:click="buatVersiBaru({{ $templateAktif->id }})" class="rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-zinc-700 dark:hover:bg-zinc-800">Buat versi baru</button>
                                <button type="button" wire:click="mintaHapusTemplate({{ $templateAktif->id }})" class="rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">Hapus template</button>
                            </div>
                        @endif
                    </div>

                    @if ($templateAktif && $templateHapusId === $templateAktif->id)
                        <div role="alert" class="mt-4 rounded-xl border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                            <p class="font-semibold">Hapus {{ $templateAktif->nama }} v{{ $templateAktif->versi }}?</p>
                            <p class="mt-1 text-xs leading-5">Layout dan seluruh PNG pada versi ini akan dihapus permanen. Riwayat hasil konten tetap tersimpan.</p>
                            <div class="mt-3 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                                <button type="button" wire:click="batalHapusTemplate" class="min-h-10 rounded-lg border border-red-300 bg-white px-3 py-2 text-xs font-semibold text-red-800 hover:bg-red-100 dark:border-red-800 dark:bg-red-950 dark:text-red-200">Batal</button>
                                <button type="button" wire:click="hapusTemplate({{ $templateAktif->id }})" wire:loading.attr="disabled" wire:target="hapusTemplate" class="min-h-10 rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-700 disabled:opacity-50"><span wire:loading.remove wire:target="hapusTemplate">Ya, hapus versi ini</span><span wire:loading wire:target="hapusTemplate">Menghapus...</span></button>
                            </div>
                        </div>
                    @endif

                    @if ($templateAktif?->status === 'aktif')
                        <div class="mt-5 flex flex-col gap-3 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100 sm:flex-row sm:items-center sm:justify-between">
                            <span>Template aktif dikunci agar desain yang sedang dipakai tidak berubah.</span>
                            <button type="button" wire:click="buatVersiBaru({{ $templateAktif->id }})" class="shrink-0 rounded-lg bg-amber-900 px-3 py-2 text-xs font-bold text-amber-50 focus:outline-none focus:ring-2 focus:ring-amber-500 dark:bg-amber-300 dark:text-amber-950">Buat versi baru</button>
                        </div>
                    @endif

                    <fieldset @disabled($templateAktif && $templateAktif->status !== 'draf') class="mt-5 space-y-5 disabled:opacity-60">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold sm:col-span-2">Nama
                                <input wire:model.live.debounce.300ms="nama" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950">
                                @error('nama') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="text-sm font-semibold">Format
                                <input wire:model.live="format" list="format-template" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950">
                                <datalist id="format-template">
                                    <option value="ig_carousel">Carousel IG + FB</option>
                                    <option value="video_vertikal">Video Shorts + TikTok</option>
                                    @foreach ($templates->pluck('format')->unique() as $formatItem)<option value="{{ $formatItem }}">@endforeach
                                </datalist>
                                @error('format') <span class="mt-1 block text-xs text-red-600">Gunakan huruf kecil, angka, atau garis bawah.</span> @enderror
                            </label>
                            <label class="text-sm font-semibold">Rasio
                                <input wire:model="rasio" inputmode="numeric" placeholder="4:5" type="text" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950">
                                @error('rasio') <span class="mt-1 block text-xs text-red-600">Gunakan format seperti 4:5.</span> @enderror
                            </label>
                        </div>

                        @if ($format === 'ig_carousel')
                            @php($jumlahBackground = $templateAktif?->aset?->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() ?? 0)
                            <div>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-bold">Background carousel</h3>
                                        <p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">Pilih tiga PNG 1080 × 1350. Setelah preview muncul, tekan <strong>Simpan background</strong> agar file masuk ke template.</p>
                                    </div>
                                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $jumlahBackground === 3 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200' }}">{{ $jumlahBackground }}/3 tersimpan</span>
                                </div>
                                <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
                                    @foreach ([0, 1, 2] as $index)
                                        @php($aset = $templateAktif?->aset?->firstWhere('jenis', 'background_slide_'.($index + 1)))
                                        <label class="block text-xs font-semibold">Slide {{ $index + 1 }}
                                            <span class="mt-2 block aspect-[4/5] overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-950">
                                                @if (isset($backgroundSlides[$index]))
                                                    <img src="{{ $backgroundSlides[$index]->temporaryUrl() }}" alt="Preview background slide {{ $index + 1 }}" class="h-full w-full object-cover">
                                                @elseif ($aset)
                                                    <img src="{{ route('visual.template.aset', [$templateAktif, $aset]) }}" alt="Background slide {{ $index + 1 }}" class="h-full w-full object-cover">
                                                @else
                                                    <span class="grid h-full place-items-center px-2 text-center font-normal text-zinc-500">Belum diunggah</span>
                                                @endif
                                            </span>
                                            <span class="mt-2 block font-medium {{ isset($backgroundSlides[$index]) ? 'text-amber-700 dark:text-amber-300' : ($aset ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500') }}">
                                                {{ isset($backgroundSlides[$index]) ? 'Siap disimpan' : ($aset ? 'Tersimpan' : 'Belum tersimpan') }}
                                            </span>
                                            <input wire:model="backgroundSlides.{{ $index }}" type="file" accept="image/png" class="mt-1.5 block w-full text-[.68rem] font-normal file:mr-2 file:rounded-lg file:border-0 file:bg-zinc-100 file:px-2 file:py-1.5 file:text-xs file:font-semibold dark:file:bg-zinc-800">
                                            @error("backgroundSlides.{$index}") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                        </label>
                                    @endforeach
                                </div>
                                @error('backgroundSlides') <p class="mt-3 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                <div class="mt-4 flex justify-end">
                                    <button type="button" wire:click="simpanBackgroundCarousel" wire:loading.attr="disabled" wire:target="simpanBackgroundCarousel,backgroundSlides" class="rounded-xl border border-slate-900 px-4 py-2.5 text-sm font-semibold text-slate-900 hover:bg-stone-100 focus:outline-none focus:ring-2 focus:ring-amber-500 disabled:cursor-wait disabled:opacity-50 dark:border-zinc-300 dark:text-zinc-100 dark:hover:bg-zinc-800">
                                        <span wire:loading.remove wire:target="simpanBackgroundCarousel">Simpan background</span>
                                        <span wire:loading wire:target="simpanBackgroundCarousel">Menyimpan...</span>
                                    </button>
                                </div>
                            </div>

                            @php($penempatanAktif = $penempatanSlides[$slidePenempatanAktif] ?? [])
                            @php($kotakFotoAktif = $penempatanAktif['foto_slots'][$slotPenempatanAktif] ?? [])
                            @php($kotakTeksAktif = $penempatanAktif['teks'] ?? [])
                            @php($backgroundPenempatan = $templateAktif?->aset?->firstWhere('jenis', 'background_slide_'.($slidePenempatanAktif + 1)))
                            <div class="border-t border-stone-200 pt-5 dark:border-zinc-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div><h3 class="text-sm font-bold">Penempatan konten</h3><p class="mt-1 max-w-2xl text-xs leading-5 text-slate-500">Tentukan kotak foto dan area teks untuk setiap slide. Nilai memakai piksel dari kanvas 1080 × 1350.</p></div>
                                    <span class="shrink-0 text-xs text-slate-500">px</span>
                                </div>
                                <div class="mt-3 grid grid-cols-3 gap-2" aria-label="Pilih slide penempatan">
                                    @foreach ([0, 1, 2] as $index)
                                        <button type="button" wire:click="pilihSlidePenempatan({{ $index }})" aria-pressed="{{ $slidePenempatanAktif === $index ? 'true' : 'false' }}" class="min-h-10 rounded-lg border px-3 py-2 text-xs font-semibold {{ $slidePenempatanAktif === $index ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200' : 'border-stone-300 bg-white text-slate-600 hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' }}">Slide {{ $index + 1 }}</button>
                                    @endforeach
                                </div>

                                <div class="mt-4 grid gap-5 md:grid-cols-[minmax(0,1fr)_17rem]">
                                    <div class="relative mx-auto aspect-[4/5] w-full max-w-sm overflow-hidden rounded-xl border border-stone-300 bg-stone-100 dark:border-zinc-700 dark:bg-zinc-950">
                                        @if (isset($backgroundSlides[$slidePenempatanAktif]))
                                            <img src="{{ $backgroundSlides[$slidePenempatanAktif]->temporaryUrl() }}" alt="Preview background slide {{ $slidePenempatanAktif + 1 }}" class="absolute inset-0 h-full w-full object-cover">
                                        @elseif ($backgroundPenempatan)
                                            <img src="{{ route('visual.template.aset', [$templateAktif, $backgroundPenempatan]) }}" alt="Background slide {{ $slidePenempatanAktif + 1 }}" class="absolute inset-0 h-full w-full object-cover">
                                        @endif
                                        @foreach (($penempatanAktif['foto_slots'] ?? []) as $slot => $kotak)
                                            <button type="button" wire:click="pilihSlotPenempatan({{ $slot }})" aria-label="Atur area foto {{ $slot + 1 }}" class="absolute grid place-items-center overflow-hidden border-2 text-xs font-bold {{ $slotPenempatanAktif === $slot ? 'border-indigo-600 bg-indigo-100/45 text-indigo-950 ring-2 ring-indigo-200' : 'border-indigo-400 bg-indigo-50/25 text-indigo-800' }}" style="left:{{ (float) ($kotak['x'] ?? 0) / 10.8 }}%;top:{{ (float) ($kotak['y'] ?? 0) / 13.5 }}%;width:{{ (float) ($kotak['lebar'] ?? 0) / 10.8 }}%;height:{{ (float) ($kotak['tinggi'] ?? 0) / 13.5 }}%;border-radius:{{ (float) ($kotak['radius'] ?? 0) / 10.8 }}%">Foto {{ $slot + 1 }}</button>
                                        @endforeach
                                        <div class="pointer-events-none absolute grid place-items-center border-2 border-dashed border-emerald-700 bg-emerald-50/30 px-2 text-center text-xs font-bold text-emerald-950" style="left:{{ (float) ($kotakTeksAktif['x'] ?? 0) / 10.8 }}%;top:{{ (float) ($kotakTeksAktif['y'] ?? 0) / 13.5 }}%;width:{{ (float) ($kotakTeksAktif['lebar'] ?? 40) / 10.8 }}%;height:{{ (float) ($kotakTeksAktif['tinggi'] ?? 40) / 13.5 }}%">Area teks</div>
                                    </div>

                                    <div class="space-y-5">
                                        @if ($slidePenempatanAktif === 0)
                                            <div><p class="text-xs font-semibold">Pilih kotak foto</p><div class="mt-2 grid grid-cols-3 gap-1">@foreach (['Utama', 'Atas', 'Bawah'] as $slot => $label)<button type="button" wire:click="pilihSlotPenempatan({{ $slot }})" class="min-h-9 rounded-lg border px-1.5 py-2 text-[.68rem] font-semibold {{ $slotPenempatanAktif === $slot ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200' : 'border-stone-300 dark:border-zinc-700' }}">{{ $label }}</button>@endforeach</div></div>
                                        @endif

                                        <div wire:key="penempatan-foto-{{ $slidePenempatanAktif }}-{{ $slotPenempatanAktif }}">
                                            <p class="text-xs font-bold">Kotak foto {{ $slotPenempatanAktif + 1 }}</p>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                @foreach ([['x', 'X'], ['y', 'Y'], ['lebar', 'Lebar'], ['tinggi', 'Tinggi'], ['radius', 'Radius']] as [$field, $label])
                                                    <label class="text-xs font-semibold">{{ $label }}<input type="number" min="0" max="{{ in_array($field, ['y', 'tinggi']) ? 1350 : 1080 }}" wire:model.live.debounce.250ms="penempatanSlides.{{ $slidePenempatanAktif }}.foto_slots.{{ $slotPenempatanAktif }}.{{ $field }}" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900"></label>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold">Area teks</p>
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                @foreach ([['x', 'X'], ['y', 'Y'], ['lebar', 'Lebar'], ['tinggi', 'Tinggi']] as [$field, $label])
                                                    <label class="text-xs font-semibold">{{ $label }}<input type="number" min="0" max="{{ in_array($field, ['y', 'tinggi']) ? 1350 : 1080 }}" wire:model.live.debounce.250ms="penempatanSlides.{{ $slidePenempatanAktif }}.teks.{{ $field }}" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900"></label>
                                                @endforeach
                                            </div>
                                        </div>
                                        @error("penempatanSlides.{$slidePenempatanAktif}") <p class="text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                                        <button type="button" wire:click="resetPenempatanSlide" class="min-h-10 w-full rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs font-semibold hover:bg-stone-100 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">Reset slide {{ $slidePenempatanAktif + 1 }}</button>
                                    </div>
                                </div>
                            </div>
                        @elseif ($format === 'video_vertikal')
                            @php($sceneVideo = $videoScenes[$videoSceneAktif] ?? ['durasi' => 8, 'layers' => []])
                            @php($layerVideo = $sceneVideo['layers'][$videoLayerAktif] ?? null)
                            <section class="border-t border-stone-200 pt-5 dark:border-zinc-800" aria-labelledby="editor-template-video">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <h3 id="editor-template-video" class="text-sm font-bold">Penyusun template video</h3>
                                        <p class="mt-1 max-w-3xl text-xs leading-5 text-slate-500 dark:text-zinc-400">Susun tepat 3 scene pada kanvas 1080 × 1920. Upload header, footer, logo, dan ornamen sebagai PNG terpisah agar setiap elemen bisa dianimasikan sendiri.</p>
                                    </div>
                                    <span class="w-fit shrink-0 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">9:16 · 1080 × 1920 px</span>
                                </div>

                                <div class="mt-4 grid grid-cols-3 gap-2" aria-label="Pilih scene video">
                                    @foreach ([0, 1, 2] as $sceneIndex)
                                        <button type="button" wire:click="pilihVideoScene({{ $sceneIndex }})" aria-pressed="{{ $videoSceneAktif === $sceneIndex ? 'true' : 'false' }}" class="min-h-11 rounded-xl border px-3 py-2 text-xs font-bold transition {{ $videoSceneAktif === $sceneIndex ? 'border-indigo-500 bg-indigo-50 text-indigo-800 ring-2 ring-indigo-100 dark:bg-indigo-950 dark:text-indigo-200 dark:ring-indigo-950' : 'border-stone-300 text-slate-600 hover:border-indigo-300 dark:border-zinc-700 dark:text-zinc-300' }}">
                                            Scene {{ $sceneIndex + 1 }}
                                            <span class="mt-0.5 block text-[.65rem] font-medium opacity-70">{{ count($videoScenes[$sceneIndex]['layers'] ?? []) }} layer</span>
                                        </button>
                                    @endforeach
                                </div>

                                <section
                                    class="mt-4 border-y border-stone-200 py-4 dark:border-zinc-800 disabled:pointer-events-auto disabled:opacity-100"
                                    aria-labelledby="preview-template-video"
                                    x-data="{
                                        scene: {{ $videoSceneAktif }},
                                        durasi: @js(array_map(fn ($item) => (float) ($item['durasi'] ?? 8), $videoScenes)),
                                        mode: 'scene',
                                        bermain: false,
                                        jeda: false,
                                        waktuScene: 0,
                                        terakhir: 0,
                                        timer: null,
                                        mulai(mode) {
                                            this.hentikan();
                                            this.mode = mode;
                                            this.scene = mode === 'semua' ? 0 : {{ $videoSceneAktif }};
                                            this.waktuScene = 0;
                                            this.terakhir = performance.now();
                                            this.bermain = false;
                                            this.$nextTick(() => {
                                                this.bermain = true;
                                                this.timer = setInterval(() => this.detak(), 80);
                                            });
                                        },
                                        detak() {
                                            const sekarang = performance.now();
                                            this.waktuScene += (sekarang - this.terakhir) / 1000;
                                            if (this.waktuScene >= (this.durasi[this.scene] || 8)) {
                                                if (this.mode === 'semua' && this.scene < 2) {
                                                    this.scene++;
                                                    this.waktuScene = 0;
                                                    this.bermain = false;
                                                    this.$nextTick(() => this.bermain = true);
                                                } else {
                                                    this.hentikan(false);
                                                }
                                            }
                                            this.terakhir = sekarang;
                                        },
                                        toggleJeda() {
                                            if (this.bermain) {
                                                this.bermain = false;
                                                this.jeda = true;
                                            } else if (this.jeda) {
                                                this.bermain = true;
                                                this.jeda = false;
                                            } else {
                                                return;
                                            }
                                            this.terakhir = performance.now();
                                        },
                                        hentikan(reset = true) {
                                            if (this.timer) clearInterval(this.timer);
                                            this.timer = null;
                                            this.bermain = false;
                                            this.jeda = false;
                                            if (reset) this.waktuScene = 0;
                                        }
                                    }"
                                >
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div>
                                            <h4 id="preview-template-video" class="text-sm font-semibold">Preview animasi</h4>
                                            <p class="mt-1 text-xs leading-5 text-slate-500">Periksa gerakan dan waktu masuk setiap layer sebelum template disimpan.</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2 sm:flex">
                                            <a href="#preview-template-video" role="button" x-on:click.prevent="mulai('scene')" class="grid min-h-10 place-items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">Putar scene aktif</a>
                                            <a href="#preview-template-video" role="button" x-on:click.prevent="mulai('semua')" class="grid min-h-10 place-items-center rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs font-semibold hover:bg-stone-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">Preview semua scene</a>
                                            <a href="#preview-template-video" role="button" x-on:click.prevent="toggleJeda" x-bind:aria-disabled="! bermain && ! jeda" x-bind:class="(! bermain && ! jeda) ? 'pointer-events-none opacity-50' : ''" class="grid min-h-10 place-items-center rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs font-semibold dark:border-zinc-700 dark:bg-zinc-900"><span x-text="jeda ? 'Lanjutkan' : 'Jeda'">Jeda</span></a>
                                            <a href="#preview-template-video" role="button" x-on:click.prevent="mulai(mode)" class="grid min-h-10 place-items-center rounded-lg border border-stone-300 bg-white px-3 py-2 text-xs font-semibold dark:border-zinc-700 dark:bg-zinc-900">Ulangi</a>
                                        </div>
                                    </div>

                                    <div class="mt-4 grid gap-4 lg:grid-cols-[minmax(15rem,23rem)_minmax(0,1fr)] lg:items-center">
                                        <div class="relative mx-auto aspect-[9/16] w-full max-w-[23rem] overflow-hidden rounded-xl bg-zinc-100 ring-1 ring-zinc-300 dark:bg-zinc-950 dark:ring-zinc-700">
                                            @foreach ($videoScenes as $previewSceneIndex => $previewScene)
                                                <div
                                                    x-show="scene === {{ $previewSceneIndex }}"
                                                    x-bind:class="{ 'proud-template-preview-playing': bermain && scene === {{ $previewSceneIndex }} }"
                                                    class="absolute inset-0"
                                                >
                                                    @foreach (collect($previewScene['layers'] ?? [])->sortBy('urutan') as $previewLayerIndex => $previewLayer)
                                                        @php($jenisAsetPreview = 'video_scene_'.($previewSceneIndex + 1).'_'.$previewLayer['id'])
                                                        @php($asetPreview = $templateAktif?->aset?->firstWhere('jenis', $jenisAsetPreview))
                                                        @php($uploadPreview = $videoLayerUploads[$previewSceneIndex][$previewLayerIndex] ?? null)
                                                        @php($teksPreview = match ($previewLayer['id']) { 'tanggal' => 'BANDUNG · 1 AGUSTUS 2026', 'judul' => 'Judul utama kegiatan', 'subjudul' => 'Ringkasan singkat kegiatan untuk melengkapi judul.', 'paragraf_1' => 'Paragraf pertama merangkum inti kegiatan secara informatif.', 'paragraf_2' => 'Paragraf kedua menjelaskan dampak kegiatan kepada masyarakat.', default => $previewLayer['nama'] })
                                                        <div
                                                            data-proud-animation="{{ $previewLayer['animasi'] }}"
                                                            class="absolute overflow-hidden"
                                                            style="left:{{ (float) ($previewLayer['x'] ?? 0) / 10.8 }}%;top:{{ (float) ($previewLayer['y'] ?? 0) / 19.2 }}%;width:{{ (float) ($previewLayer['lebar'] ?? 0) / 10.8 }}%;height:{{ (float) ($previewLayer['tinggi'] ?? 0) / 19.2 }}%;z-index:{{ (int) ($previewLayer['urutan'] ?? 0) }};--layer-mulai:{{ (float) ($previewLayer['mulai'] ?? 0) }}s;--layer-durasi:{{ max(.01, (float) ($previewLayer['durasi_animasi'] ?? 0)) }}s"
                                                        >
                                                            @if ($previewLayer['jenis'] === 'png' && $uploadPreview)
                                                                <img src="{{ $uploadPreview->temporaryUrl() }}" alt="Preview {{ $previewLayer['nama'] }}" class="h-full w-full object-contain">
                                                            @elseif ($previewLayer['jenis'] === 'png' && $asetPreview)
                                                                <img src="{{ route('visual.template.aset', [$templateAktif, $asetPreview]) }}" alt="{{ $previewLayer['nama'] }}" class="h-full w-full object-contain">
                                                            @elseif ($previewLayer['jenis'] === 'foto')
                                                                <div class="grid h-full place-items-center bg-zinc-300 px-3 text-center text-xs font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-200">Foto kegiatan</div>
                                                            @elseif (in_array($previewLayer['jenis'], ['judul', 'paragraf'], true))
                                                                <div class="flex h-full items-center overflow-hidden whitespace-pre-line px-[3%] font-['Roboto'] leading-tight text-[#172a5d] {{ $previewLayer['jenis'] === 'judul' ? 'text-[clamp(.65rem,2.5vw,1.6rem)] font-extrabold' : 'text-[clamp(.5rem,1.7vw,1.05rem)] font-semibold leading-snug' }}">{{ $teksPreview }}</div>
                                                            @else
                                                                <div class="grid h-full place-items-center border border-dashed border-zinc-400 px-2 text-center text-xs text-zinc-500">{{ $previewLayer['nama'] }}</div>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endforeach
                                            <span class="absolute left-3 top-3 z-[110] rounded-full bg-zinc-950/75 px-2.5 py-1 text-xs font-semibold text-white">Scene <span x-text="scene + 1">{{ $videoSceneAktif + 1 }}</span>/3</span>
                                        </div>

                                        <div class="space-y-3 text-sm">
                                            <div class="flex items-center justify-between gap-3 border-b border-stone-200 pb-3 dark:border-zinc-800"><span class="text-slate-500">Status</span><strong x-text="bermain ? (jeda ? 'Dijeda' : 'Sedang diputar') : 'Siap diputar'">Siap diputar</strong></div>
                                            <div class="flex items-center justify-between gap-3 border-b border-stone-200 pb-3 dark:border-zinc-800"><span class="text-slate-500">Mode</span><strong x-text="mode === 'semua' ? 'Semua scene' : 'Scene aktif'">Scene aktif</strong></div>
                                            <div class="flex items-center justify-between gap-3"><span class="text-slate-500">Durasi scene</span><strong><span x-text="durasi[scene] || 8">{{ $sceneVideo['durasi'] ?? 8 }}</span> detik</strong></div>
                                            <p class="text-xs leading-5 text-slate-500">Preview memakai posisi, urutan, animasi, waktu mulai, durasi, dan PNG yang sedang terlihat pada form. Untuk pengguna yang mengurangi animasi, layer ditampilkan tanpa gerakan.</p>
                                        </div>
                                    </div>
                                </section>

                                <div class="mt-4 grid min-w-0 gap-4 xl:grid-cols-[15rem_minmax(22rem,1fr)_19rem]">
                                    <div class="min-w-0 border-b border-stone-200 pb-4 dark:border-zinc-800 xl:border-b-0 xl:border-r xl:pb-0 xl:pr-4">
                                        <div class="flex items-center justify-between gap-2">
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Layer scene {{ $videoSceneAktif + 1 }}</h4>
                                            <span class="text-[.68rem] text-slate-500">urutan</span>
                                        </div>
                                        <div class="mt-2 max-h-[38rem] space-y-1.5 overflow-y-auto pr-1">
                                            @foreach (($sceneVideo['layers'] ?? []) as $layerIndex => $layer)
                                                <button type="button" wire:click="pilihVideoLayer({{ $layerIndex }})" wire:key="video-layer-list-{{ $videoSceneAktif }}-{{ $layer['id'] }}" class="flex min-h-11 w-full items-center gap-2 rounded-lg border px-2.5 py-2 text-left transition {{ $videoLayerAktif === $layerIndex ? 'border-indigo-500 bg-indigo-50 text-indigo-900 dark:bg-indigo-950 dark:text-indigo-100' : 'border-transparent hover:border-stone-300 hover:bg-stone-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-800' }}">
                                                    <span class="grid size-7 shrink-0 place-items-center rounded-md bg-stone-100 text-[.6rem] font-black uppercase text-slate-600 dark:bg-zinc-800 dark:text-zinc-300">{{ match ($layer['jenis']) { 'png' => 'PNG', 'foto' => 'IMG', 'judul' => 'T', default => '¶' } }}</span>
                                                    <span class="min-w-0 flex-1"><strong class="block truncate text-xs">{{ $layer['nama'] }}</strong><span class="block text-[.65rem] text-slate-500">{{ str($layer['animasi'])->replace('_', ' ') }}</span></span>
                                                    <span class="text-[.65rem] font-semibold text-slate-400">{{ $layer['urutan'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>

                                        <div class="mt-4 border-t border-stone-200 pt-4 dark:border-zinc-800">
                                            <label class="text-xs font-semibold">Tambah layer
                                                <select wire:model="jenisLayerBaru" class="mt-1.5 w-full rounded-lg border-stone-300 bg-white text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                                    <option value="png">Elemen PNG</option>
                                                    <option value="foto">Area foto</option>
                                                    <option value="judul">Area judul</option>
                                                    <option value="paragraf">Area paragraf</option>
                                                </select>
                                            </label>
                                            <button type="button" wire:click="tambahVideoLayer" class="mt-2 min-h-10 w-full rounded-lg bg-slate-900 px-3 py-2 text-xs font-bold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-zinc-100 dark:text-zinc-950">Tambah ke scene</button>
                                            <button type="button" wire:click="resetVideoScene" class="mt-2 min-h-10 w-full rounded-lg border border-stone-300 px-3 py-2 text-xs font-semibold hover:bg-stone-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Reset scene {{ $videoSceneAktif + 1 }}</button>
                                        </div>
                                    </div>

                                    <div class="min-w-0">
                                        <div class="flex items-center justify-between gap-3">
                                            <div><h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Kanvas kerja</h4><p class="mt-1 text-[.68rem] text-slate-500">Klik elemen untuk mengaturnya.</p></div>
                                            <label class="flex items-center gap-2 text-xs font-semibold">Durasi scene
                                                <input wire:model.live.debounce.250ms="videoScenes.{{ $videoSceneAktif }}.durasi" type="number" min="3" max="15" class="w-16 rounded-lg border-stone-300 bg-white text-right text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                                <span class="font-normal text-slate-500">dtk</span>
                                            </label>
                                        </div>
                                        <div class="mt-3 flex min-h-[42rem] items-center justify-center rounded-xl bg-stone-100 p-3 dark:bg-zinc-950 sm:p-5">
                                            <div class="relative aspect-[9/16] h-[40rem] max-h-[72vh] max-w-full overflow-hidden bg-zinc-200 shadow-xl ring-1 ring-zinc-300 dark:bg-zinc-900 dark:ring-zinc-700">
                                                @foreach (($sceneVideo['layers'] ?? []) as $layerIndex => $layer)
                                                    @php($jenisAsetVideo = 'video_scene_'.($videoSceneAktif + 1).'_'.$layer['id'])
                                                    @php($asetVideo = $templateAktif?->aset?->firstWhere('jenis', $jenisAsetVideo))
                                                    @php($uploadVideo = $videoLayerUploads[$videoSceneAktif][$layerIndex] ?? null)
                                                    <button type="button" wire:click="pilihVideoLayer({{ $layerIndex }})" wire:key="video-layer-canvas-{{ $videoSceneAktif }}-{{ $layer['id'] }}" aria-label="Atur {{ $layer['nama'] }}" class="absolute overflow-hidden text-left transition {{ $videoLayerAktif === $layerIndex ? 'ring-2 ring-inset ring-indigo-500' : 'ring-1 ring-inset ring-slate-500/35 hover:ring-indigo-400' }}" style="left:{{ (float) ($layer['x'] ?? 0) / 10.8 }}%;top:{{ (float) ($layer['y'] ?? 0) / 19.2 }}%;width:{{ (float) ($layer['lebar'] ?? 0) / 10.8 }}%;height:{{ (float) ($layer['tinggi'] ?? 0) / 19.2 }}%;z-index:{{ (int) ($layer['urutan'] ?? 0) }}">
                                                        @if ($layer['jenis'] === 'png' && $uploadVideo)
                                                            <img src="{{ $uploadVideo->temporaryUrl() }}" alt="Preview {{ $layer['nama'] }}" class="h-full w-full object-contain">
                                                        @elseif ($layer['jenis'] === 'png' && $asetVideo)
                                                            <img src="{{ route('visual.template.aset', [$templateAktif, $asetVideo]) }}" alt="{{ $layer['nama'] }}" class="h-full w-full object-contain">
                                                        @elseif ($layer['jenis'] === 'foto')
                                                            <span class="grid h-full place-items-center bg-slate-300/70 px-2 text-center text-[.55rem] font-bold uppercase tracking-wider text-slate-600 dark:bg-zinc-700 dark:text-zinc-300">Area foto</span>
                                                        @elseif ($layer['jenis'] === 'judul')
                                                            <span class="flex h-full items-center bg-amber-100/70 px-[4%] text-[.7rem] font-black leading-tight text-slate-800 dark:bg-amber-950/70 dark:text-amber-100">{{ $layer['nama'] }}</span>
                                                        @elseif ($layer['jenis'] === 'paragraf')
                                                            <span class="flex h-full items-center bg-emerald-100/60 px-[4%] text-[.55rem] font-medium leading-relaxed text-slate-700 dark:bg-emerald-950/60 dark:text-emerald-100">{{ $layer['nama'] }}. Teks konten akan mengisi area ini.</span>
                                                        @else
                                                            <span class="grid h-full place-items-center bg-white/40 px-1 text-center text-[.5rem] font-semibold text-slate-500">Upload PNG</span>
                                                        @endif
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>

                                    <div class="min-w-0 border-t border-stone-200 pt-4 dark:border-zinc-800 xl:border-t-0 xl:border-l xl:pl-4 xl:pt-0">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-zinc-400">Pengaturan layer</h4>
                                        @if ($layerVideo)
                                            <div wire:key="video-layer-controls-{{ $videoSceneAktif }}-{{ $layerVideo['id'] }}" class="mt-3 space-y-4">
                                                <label class="block text-xs font-semibold">Nama layer
                                                    <input wire:model.live.debounce.250ms="videoScenes.{{ $videoSceneAktif }}.layers.{{ $videoLayerAktif }}.nama" type="text" maxlength="60" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                                </label>

                                                @if ($layerVideo['jenis'] === 'png')
                                                    @php($jenisAsetAktif = 'video_scene_'.($videoSceneAktif + 1).'_'.$layerVideo['id'])
                                                    @php($asetAktifVideo = $templateAktif?->aset?->firstWhere('jenis', $jenisAsetAktif))
                                                    <label class="block rounded-lg bg-stone-100 p-3 text-xs font-semibold dark:bg-zinc-950">File PNG
                                                        <span class="mt-1 block font-normal leading-4 text-slate-500">Gunakan file terpotong sesuai elemennya. Background penuh boleh 1080 × 1920.</span>
                                                        <input wire:model="videoLayerUploads.{{ $videoSceneAktif }}.{{ $videoLayerAktif }}" type="file" accept="image/png" class="mt-2 block w-full text-[.65rem] font-normal file:mr-2 file:rounded-md file:border-0 file:bg-white file:px-2 file:py-1.5 file:text-[.65rem] file:font-bold dark:file:bg-zinc-800">
                                                        <span class="mt-1.5 block text-[.65rem] font-semibold {{ isset($videoLayerUploads[$videoSceneAktif][$videoLayerAktif]) ? 'text-amber-700 dark:text-amber-300' : ($asetAktifVideo ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500') }}">{{ isset($videoLayerUploads[$videoSceneAktif][$videoLayerAktif]) ? 'Siap disimpan' : ($asetAktifVideo ? 'PNG tersimpan' : 'Belum ada file') }}</span>
                                                        @error("videoLayerUploads.{$videoSceneAktif}.{$videoLayerAktif}") <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                                                    </label>
                                                @endif

                                                <div>
                                                    <p class="text-xs font-bold">Posisi dan ukuran <span class="font-normal text-slate-500">(px)</span></p>
                                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                                        @foreach ([['x', 'X', -1080, 1079], ['y', 'Y', -1920, 1919], ['lebar', 'Lebar', 40, 1080], ['tinggi', 'Tinggi', 40, 1920], ['urutan', 'Urutan', 0, 100]] as [$field, $label, $min, $max])
                                                            <label class="text-[.68rem] font-semibold">{{ $label }}<input wire:model.live.debounce.250ms="videoScenes.{{ $videoSceneAktif }}.layers.{{ $videoLayerAktif }}.{{ $field }}" type="number" min="{{ $min }}" max="{{ $max }}" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-xs dark:border-zinc-700 dark:bg-zinc-900"></label>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <div>
                                                    <p class="text-xs font-bold">Gerakan masuk</p>
                                                    <label class="mt-2 block text-[.68rem] font-semibold">Animasi
                                                        <select wire:model="videoScenes.{{ $videoSceneAktif }}.layers.{{ $videoLayerAktif }}.animasi" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-xs dark:border-zinc-700 dark:bg-zinc-900">
                                                            <option value="diam">Diam</option>
                                                            <option value="fade_in">Fade in</option>
                                                            <option value="masuk_kiri">Masuk dari kiri</option>
                                                            <option value="masuk_kanan">Masuk dari kanan</option>
                                                            <option value="naik">Naik dari bawah</option>
                                                            <option value="zoom_lembut">Zoom lembut</option>
                                                        </select>
                                                    </label>
                                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                                        <label class="text-[.68rem] font-semibold">Mulai (dtk)<input wire:model="videoScenes.{{ $videoSceneAktif }}.layers.{{ $videoLayerAktif }}.mulai" type="number" min="0" max="15" step="0.1" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-xs dark:border-zinc-700 dark:bg-zinc-900"></label>
                                                        <label class="text-[.68rem] font-semibold">Durasi (dtk)<input wire:model="videoScenes.{{ $videoSceneAktif }}.layers.{{ $videoLayerAktif }}.durasi_animasi" type="number" min="0" max="3" step="0.1" class="mt-1 w-full rounded-lg border-stone-300 bg-white text-xs dark:border-zinc-700 dark:bg-zinc-900"></label>
                                                    </div>
                                                </div>

                                                @error("videoScenes.{$videoSceneAktif}.layers.{$videoLayerAktif}") <p class="text-xs font-semibold leading-5 text-red-600">{{ $message }}</p> @enderror
                                                @error("videoScenes.{$videoSceneAktif}.layers.{$videoLayerAktif}.mulai") <p class="text-xs font-semibold leading-5 text-red-600">{{ $message }}</p> @enderror
                                                @if (! in_array($layerVideo['id'], ['background', 'foto', 'judul', 'paragraf_1'], true))
                                                    <button type="button" wire:click="hapusVideoLayer({{ $videoLayerAktif }})" class="min-h-10 w-full rounded-lg border border-red-300 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">Hapus layer</button>
                                                @endif
                                            </div>
                                        @else
                                            <p class="mt-3 text-xs leading-5 text-slate-500">Pilih layer di daftar atau kanvas.</p>
                                        @endif
                                    </div>
                                </div>
                                @error('videoScenes') <p class="mt-3 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                            </section>
                        @endif

                        @if ($format !== 'video_vertikal')
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

                            <label class="block text-sm font-semibold">Durasi per slide <span class="font-normal text-slate-500">(opsional)</span>
                                <div class="mt-1.5 flex items-center gap-2"><input wire:model="durasiPerSlide" type="number" min="1" max="60" inputmode="numeric" class="w-28 rounded-xl border-stone-300 bg-stone-50 text-sm focus:border-amber-500 focus:ring-amber-500 dark:border-zinc-700 dark:bg-zinc-950"><span class="text-sm text-slate-500">detik</span></div>
                            </label>
                        @endif
                    </fieldset>

                    <div class="mt-6 grid gap-2 sm:flex sm:justify-end">
                        @php($aktivasiBelumSiap = $format === 'ig_carousel'
                            ? (($templateAktif?->aset?->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() ?? 0) !== 3)
                            : ($format === 'video_vertikal' && (($templateAktif?->layouts?->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3'])->count() ?? 0) !== 3)))
                        @if ($templateAktif?->status === 'draf')
                            <button type="button" wire:click="simpanDanAktifkan" wire:loading.attr="disabled" @disabled($aktivasiBelumSiap) class="order-2 rounded-xl border border-emerald-700 px-4 py-2.5 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 disabled:cursor-not-allowed disabled:border-stone-300 disabled:text-stone-400 disabled:hover:bg-transparent dark:text-emerald-300 dark:hover:bg-emerald-950 dark:disabled:border-zinc-700 dark:disabled:text-zinc-600"><span wire:loading.remove wire:target="simpanDanAktifkan">Simpan & tersedia di editor</span><span wire:loading wire:target="simpanDanAktifkan">Menyimpan seluruh template...</span></button>
                        @endif
                        @if (! $templateAktif || $templateAktif->status === 'draf')
                            <button type="submit" wire:loading.attr="disabled" class="order-1 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 dark:bg-amber-400 dark:text-amber-950">Simpan draf</button>
                        @endif
                    </div>
                </form>

                <aside aria-label="Preview template" class="rounded-2xl border border-stone-300 bg-stone-200 p-4 dark:border-zinc-800 dark:bg-zinc-900 {{ $format === 'video_vertikal' ? 'hidden' : '' }}">
                    <div class="flex items-center justify-between gap-3"><h2 class="text-sm font-bold">Preview</h2><span class="text-xs text-slate-500">{{ $rasio }}</span></div>
                    @php($previewBackground = $templateAktif?->aset?->firstWhere('jenis', 'background_slide_1'))
                    <div class="relative mt-3 overflow-hidden rounded-xl bg-slate-900 text-white shadow-lg" style="aspect-ratio: {{ str_replace(':', '/', $rasio ?: '4:5') }}">
                        @if ($previewBackground)<img src="{{ route('visual.template.aset', [$templateAktif, $previewBackground]) }}" alt="Preview background template" class="absolute inset-0 h-full w-full object-cover">@endif
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
