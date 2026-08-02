<div data-proud-page>
  <main class="mx-auto max-w-7xl space-y-4 px-4 py-4 sm:px-6 sm:py-5 lg:px-8">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-xs">Ruang pengerjaan</p>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $this->tugas->judul }}</h1>
            <div class="mt-1 text-sm text-zinc-500">
                @if ($this->tugas->deadline_at)
                    Tenggat {{ $this->tugas->deadline_at->translatedFormat('l, j F Y H:i') }}
                @endif
                <span class="ml-2 rounded bg-zinc-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                    {{ $this->tugas->status }}
                </span>
            </div>
        </div>
        <div class="flex gap-2">
            @if ($this->tugas->status === 'baru')
                <button wire:click="mulaiKerjakan" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">Mulai kerjakan</button>
            @elseif ($this->tugas->status === 'dikerjakan')
                <button wire:click="tandaiSelesai" wire:confirm="Tandai bagian tugas Anda selesai? Tugas akan keluar dari daftar aktif Anda." class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-500">Tandai selesai</button>
            @endif
        </div>
    </header>

    @if ($agenda)
        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm dark:border-indigo-800 dark:bg-indigo-950">
            <div class="font-medium text-indigo-900 dark:text-indigo-200">Kegiatan: {{ $agenda->judul }}</div>
            <div class="text-indigo-700 dark:text-indigo-300">
                {{ $agenda->mulai_at->translatedFormat('l, j F Y H:i') }} · {{ $agenda->lokasi ?? 'lokasi belum diisi' }}
            </div>
        </div>
    @endif

    @if ($this->tugas->brief)
        <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="mb-2 font-semibold text-zinc-900 dark:text-white">Brief</h2>
            <p class="whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-300">{{ $this->tugas->brief }}</p>
        </section>
    @endif

    <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        @if (count($unggahan))
            <div class="mb-4 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-zinc-500">Siap diunggah</p>
                <ul class="mt-2 space-y-1 text-sm text-zinc-700 dark:text-zinc-300">@foreach ($unggahan as $file)<li wire:key="unggahan-preview-{{ $loop->index }}" class="truncate">{{ $file->getClientOriginalName() }}</li>@endforeach</ul>
            </div>
        @endif
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="font-semibold text-zinc-900 dark:text-white">Lampiran tugas</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $this->tugas->bahan->count() }} berkas referensi dari koordinator. Foto baru untuk website ditambahkan pada langkah Foto.</p>
            </div>
            <button type="button" wire:click="toggleDaftarLampiran" aria-expanded="{{ $daftarLampiranTerbuka ? 'true' : 'false' }}" class="min-h-10 shrink-0 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">
                {{ $daftarLampiranTerbuka ? 'Sembunyikan lampiran' : 'Lihat lampiran' }}<span class="sr-only">, lalu Pilih berkas bila perlu menambah lampiran umum</span>
            </button>
        </div>
        @if ($daftarLampiranTerbuka)
            <ul class="mt-4 space-y-2 border-t border-zinc-200 pt-4 text-sm dark:border-zinc-800">
                @forelse ($this->tugas->bahan as $b)
                    <li wire:key="b-{{ $b->id }}" class="flex min-w-0 items-center gap-2 text-zinc-700 dark:text-zinc-300">
                        <span aria-hidden="true">📎</span>
                        <span class="truncate">{{ $b->nama_asli }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">Belum ada bahan.</li>
                @endforelse
            </ul>
            <div class="mt-4 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <button type="button" wire:click="toggleUploaderLampiran" class="min-h-10 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-semibold hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800">{{ $uploaderLampiranTerbuka ? 'Batal tambah berkas' : 'Tambah lampiran umum' }}</button>
            </div>
        @endif
        @if ($uploaderLampiranTerbuka)
            <form wire:submit="unggahBahan" class="mt-4 space-y-3 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                <input id="unggahan-bahan" type="file" wire:model="unggahan" multiple class="block w-full text-sm">
                @if (count($unggahan))<ul class="space-y-1 text-xs text-zinc-500">@foreach ($unggahan as $file)<li wire:key="unggahan-{{ $loop->index }}" class="truncate">{{ $file->getClientOriginalName() }}</li>@endforeach</ul>@endif
                <button type="submit" @disabled(! count($unggahan)) wire:loading.attr="disabled" class="min-h-10 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="unggahBahan">Unggah lampiran</span><span wire:loading wire:target="unggahBahan">Mengunggah…</span></button>
                @error('unggahan.*') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
            </form>
        @endif
    </section>

    @if (session('catatan-lapangan-tersimpan'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">{{ session('catatan-lapangan-tersimpan') }}</div>
    @endif

    <nav class="grid grid-cols-2 gap-2 rounded-xl bg-zinc-100 p-1 dark:bg-zinc-800" aria-label="Fokus produksi konten">
        <button type="button" wire:click="pilihFokusKonten('website')" aria-current="{{ $fokusKonten === 'website' ? 'page' : 'false' }}" class="min-h-11 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors {{ $fokusKonten === 'website' ? 'bg-white text-indigo-700 shadow-sm dark:bg-zinc-900 dark:text-indigo-300' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100' }}">Website</button>
        <button type="button" wire:click="pilihFokusKonten('sosmed')" aria-current="{{ $fokusKonten === 'sosmed' ? 'page' : 'false' }}" class="min-h-11 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors {{ $fokusKonten === 'sosmed' ? 'bg-white text-indigo-700 shadow-sm dark:bg-zinc-900 dark:text-indigo-300' : 'text-zinc-600 hover:text-zinc-950 dark:text-zinc-400 dark:hover:text-zinc-100' }}">Sosmed</button>
    </nav>

    @if ($fokusKonten === 'website')
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="website-workspace-title">
        <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-800 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Fokus website</p>
            <h2 id="website-workspace-title" class="mt-1 text-xl font-semibold text-zinc-950 dark:text-zinc-100">Siapkan artikel dan foto website</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">Mulai dari satu bahan berita. Simpan narasi final, lalu pilih dan atur foto landscape 1050 × 750 px.</p>

            <ol class="mt-5 grid grid-cols-2 gap-2" aria-label="Tahap pengerjaan website">
                <li>
                    <button type="button" wire:click="pilihLangkahWebsite('narasi')" class="flex min-h-11 w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left text-sm font-semibold {{ $langkahWebsite === 'narasi' ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-zinc-200 text-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full {{ $langkahWebsite === 'narasi' ? 'bg-indigo-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">1</span>
                        Narasi
                    </button>
                </li>
                <li>
                    <button type="button" wire:click="pilihLangkahWebsite('foto')" class="flex min-h-11 w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left text-sm font-semibold {{ $langkahWebsite === 'foto' ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-zinc-200 text-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full {{ $langkahWebsite === 'foto' ? 'bg-indigo-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">2</span>
                        Foto
                    </button>
                </li>
            </ol>
            @error('websiteLangkah') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (session('website-status'))
            <div role="status" class="mx-4 mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200 sm:mx-6">
                {{ session('website-status') }}
            </div>
        @endif

        @if ($langkahWebsite === 'narasi')
            <div class="space-y-6 p-4 sm:p-6">
                <label class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                    Bahan berita
                    <span class="mt-1 block text-xs font-normal leading-5 text-zinc-500">Tempel laporan atensi, sambutan, catatan awal, atau gabungan bahan yang akan dijadikan artikel.</span>
                    <textarea wire:model="bahanWebsite" rows="10" placeholder="Tempel bahan berita di sini…" class="w-full"></textarea>
                    @error('bahanWebsite') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <div class="flex justify-end">
                    <button type="button" wire:click="buatNarasiWebsite" wire:loading.attr="disabled" wire:target="buatNarasiWebsite" @disabled(! $aiTersedia) class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="buatNarasiWebsite">Buat narasi website</span>
                        <span wire:loading wire:target="buatNarasiWebsite">AI sedang menyusun…</span>
                    </button>
                </div>

                <div wire:loading.flex wire:target="buatNarasiWebsite,koreksiNarasiWebsite" class="items-start gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/40 dark:text-indigo-200" role="status" aria-live="polite">
                    <span class="mt-1 flex gap-1" aria-hidden="true"><span class="size-2 animate-pulse rounded-full bg-indigo-500"></span><span class="size-2 animate-pulse rounded-full bg-indigo-500 [animation-delay:150ms]"></span><span class="size-2 animate-pulse rounded-full bg-indigo-500 [animation-delay:300ms]"></span></span>
                    <span><strong class="block font-semibold">AI sedang menyusun narasi</strong><span class="mt-1 block text-xs leading-5">Bahan dan versi terakhir tetap aman. Proses ini dapat memerlukan beberapa saat.</span></span>
                </div>

                @error('aiWebsite')
                    <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
                @enderror

                @if (! $aiTersedia)
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">AI belum dikonfigurasi. Bahan tetap dapat disimpan setelah konfigurasi penyedia, model, dan API key dilengkapi.</div>
                @endif

                @if ($narasiWebsite)
                    <div class="border-t border-zinc-200 pt-6 dark:border-zinc-800">
                        <div class="flex items-start justify-between gap-4">
                            <div><h3 class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Hasil narasi</h3><p class="mt-1 text-xs leading-5 text-zinc-500">Anda dapat menyunting langsung atau meminta AI memperbaiki bagian tertentu.</p></div>
                            <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Draf AI</span>
                        </div>
                        <label class="mt-4 block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                            Narasi website
                            <textarea wire:model="narasiWebsite" rows="18" class="w-full leading-7"></textarea>
                            @error('narasiWebsite') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                        </label>

                        <form wire:submit="koreksiNarasiWebsite" class="mt-5 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
                            <label class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                                Instruksi koreksi
                                <span class="mt-1 block text-xs font-normal leading-5 text-zinc-500">Contoh: pendekkan pembuka, pertahankan semua kutipan, dan buat judul ketiga lebih formal.</span>
                                <textarea wire:model="instruksiKoreksiWebsite" rows="3" placeholder="Tulis bagian yang perlu diperbaiki…" class="w-full"></textarea>
                                @error('instruksiKoreksiWebsite') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <div class="mt-3 flex justify-end">
                                <button type="submit" wire:loading.attr="disabled" wire:target="koreksiNarasiWebsite" class="min-h-11 rounded-lg border border-indigo-300 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 dark:border-indigo-800 dark:bg-zinc-900 dark:text-indigo-300 dark:hover:bg-zinc-800">
                                    <span wire:loading.remove wire:target="koreksiNarasiWebsite">Perbaiki dengan AI</span>
                                    <span wire:loading wire:target="koreksiNarasiWebsite">AI sedang memperbaiki…</span>
                                </button>
                            </div>
                        </form>

                        <div class="mt-5 flex justify-end">
                            <button type="button" wire:click="simpanNarasiWebsite" wire:loading.attr="disabled" wire:target="simpanNarasiWebsite" class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                                <span wire:loading.remove wire:target="simpanNarasiWebsite">Simpan narasi dan lanjut ke foto</span>
                                <span wire:loading wire:target="simpanNarasiWebsite">Menyimpan narasi…</span>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        @else
            @php($fotoTugas = $this->tugas->bahan->filter(fn ($bahan) => str_starts_with($bahan->mime, 'image/')))
            <div class="space-y-6 p-4 sm:p-6" x-data="{ zoom: @entangle('fotoWebsiteZoom').live, fokusX: @entangle('fotoWebsiteFokusX').live, fokusY: @entangle('fotoWebsiteFokusY').live, rotasi: @entangle('fotoWebsiteRotasi').live }">
                <div>
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div><h3 class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Pilih foto untuk ZIP</h3><p class="mt-1 text-sm leading-6 text-zinc-500">Pilih hingga 10 foto. Klik fotonya untuk membuka editor, lalu atur setiap foto secara terpisah.</p></div>
                        <form wire:submit="unggahFotoWebsite" class="flex flex-col gap-2 sm:items-end">
                            <label for="foto-website-baru" class="inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">Tambah foto</label>
                            <input id="foto-website-baru" type="file" wire:model="fotoWebsiteBaru" accept="image/jpeg,image/png,image/webp" class="sr-only">
                            @if ($fotoWebsiteBaru)<button type="submit" class="min-h-10 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Gunakan foto terpilih</button>@endif
                        </form>
                    </div>
                    @error('fotoWebsiteBaru') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                    @if ($fotoTugas->isNotEmpty())
                        <div class="mt-4 flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ count($fotoWebsiteTerpilih) }} foto dipilih</p>
                            <p class="text-xs text-zinc-500">Maksimal 10 foto</p>
                        </div>
                        <div class="mt-3 flex gap-3 overflow-x-auto pb-2">
                            @foreach ($fotoTugas as $foto)
                                @php($fotoTerpilih = in_array($foto->id, $fotoWebsiteTerpilih, true))
                                <div wire:key="foto-website-{{ $foto->id }}" class="w-36 shrink-0 rounded-xl border p-2 transition-colors {{ $fotoWebsiteBahanId === $foto->id ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100 dark:bg-indigo-950/40 dark:ring-indigo-950' : ($fotoTerpilih ? 'border-indigo-300 bg-indigo-50/50 dark:border-indigo-800 dark:bg-indigo-950/20' : 'border-zinc-200 dark:border-zinc-700') }}">
                                    <button type="button" wire:click="pilihFotoWebsite({{ $foto->id }})" class="block w-full rounded-lg text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2">
                                        <span class="relative block">
                                            <img src="{{ route('tugas.bahan.foto', [$this->tugas, $foto]) }}" alt="{{ $foto->nama_asli }}" class="aspect-[7/5] w-full rounded-lg object-cover">
                                            @if ($fotoWebsiteBahanId === $foto->id)
                                                <span class="absolute bottom-1 left-1 rounded-md bg-zinc-950/80 px-1.5 py-0.5 text-[10px] font-semibold text-white">Sedang diedit</span>
                                            @endif
                                        </span>
                                        <span class="mt-2 block truncate text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $foto->nama_asli }}</span>
                                    </button>
                                    <button type="button" wire:click="toggleFotoWebsite({{ $foto->id }})" aria-pressed="{{ $fotoTerpilih ? 'true' : 'false' }}" class="mt-2 flex min-h-9 w-full items-center justify-center gap-1.5 rounded-lg border px-2 py-1.5 text-xs font-semibold transition-colors {{ $fotoTerpilih ? 'border-indigo-600 bg-indigo-600 text-white hover:bg-indigo-700' : 'border-zinc-300 bg-white text-zinc-700 hover:border-indigo-400 hover:text-indigo-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200' }}">
                                        <span aria-hidden="true">{{ $fotoTerpilih ? '✓' : '+' }}</span>
                                        {{ $fotoTerpilih ? 'Terpilih' : 'Pilih' }}
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-4 rounded-xl border border-dashed border-zinc-300 px-5 py-8 text-center dark:border-zinc-700"><p class="text-sm font-semibold">Belum ada foto</p><p class="mt-1 text-xs leading-5 text-zinc-500">Tambahkan satu foto JPG, PNG, atau WebP untuk mulai mengatur foto website.</p></div>
                    @endif
                </div>

                @error('fotoWebsiteTerpilih') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                @if ($fotoWebsiteBahanId && ($fotoAktif = $fotoTugas->firstWhere('id', $fotoWebsiteBahanId)))
                    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_20rem]">
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3"><h3 class="text-sm font-semibold">Preview 1050 × 750 px</h3><span class="text-xs text-zinc-500">JPG · rasio terkunci</span></div>
                            <div class="relative aspect-[7/5] overflow-hidden rounded-xl bg-zinc-100 dark:bg-zinc-950">
                                <img src="{{ route('tugas.bahan.foto', [$this->tugas, $fotoAktif]) }}" alt="Preview foto website yang sedang diedit" class="absolute inset-0 h-full w-full object-cover" :style="`object-position: ${fokusX}% ${fokusY}%; transform: rotate(${rotasi}deg) scale(${zoom * (Math.abs(Math.cos(rotasi * Math.PI / 180)) + 1.4 * Math.abs(Math.sin(rotasi * Math.PI / 180)))}); transform-origin: center`">
                                <div class="pointer-events-none absolute inset-0 border border-black/10 dark:border-white/10"></div>
                            </div>
                        </div>

                        <div class="space-y-5 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
                            <label class="block text-sm font-semibold">Rotasi <span class="float-right text-xs font-normal text-zinc-500" x-text="`${rotasi > 0 ? '+' : ''}${rotasi}°`"></span><input type="range" x-model.number="rotasi" min="-180" max="180" step="1" class="mt-3 w-full"><span class="mt-1 flex justify-between text-[11px] font-normal text-zinc-500"><span>−180°</span><span>0°</span><span>+180°</span></span></label>
                            <label class="block text-sm font-semibold">Perbesar <span class="float-right text-xs font-normal text-zinc-500" x-text="`${Math.round(zoom * 100)}%`"></span><input type="range" x-model.number="zoom" min="1" max="3" step="0.05" class="mt-3 w-full"></label>
                            <label class="block text-sm font-semibold">Posisi horizontal <span class="float-right text-xs font-normal text-zinc-500" x-text="`${fokusX}%`"></span><input type="range" x-model.number="fokusX" min="0" max="100" step="1" class="mt-3 w-full"></label>
                            <label class="block text-sm font-semibold">Posisi vertikal <span class="float-right text-xs font-normal text-zinc-500" x-text="`${fokusY}%`"></span><input type="range" x-model.number="fokusY" min="0" max="100" step="1" class="mt-3 w-full"></label>
                            <button type="button" wire:click="resetEditorFotoWebsite" class="min-h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">Reset edit foto ini</button>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                        <button type="button" wire:click="pilihLangkahWebsite('narasi')" class="min-h-11 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">Kembali ke narasi</button>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            @if ($fotoWebsiteTersimpan)
                                <button type="button" wire:click="unduhFotoWebsite" wire:loading.attr="disabled" wire:target="unduhFotoWebsite" class="min-h-11 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"><span wire:loading.remove wire:target="unduhFotoWebsite">Unduh ulang ZIP</span><span wire:loading wire:target="unduhFotoWebsite">Menyiapkan ZIP…</span></button>
                            @endif
                            <button type="button" wire:click="simpanFotoWebsite" wire:loading.attr="disabled" wire:target="simpanFotoWebsite" class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="simpanFotoWebsite">Simpan {{ count($fotoWebsiteTerpilih) }} foto & unduh ZIP</span><span wire:loading wire:target="simpanFotoWebsite">Menyiapkan ZIP…</span></button>
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </section>
    @else
    <section class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-labelledby="sosmed-workspace-title">
        <div class="border-b border-zinc-200 px-4 py-5 dark:border-zinc-800 sm:px-6">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-400">Fokus sosmed</p>
            <h2 id="sosmed-workspace-title" class="mt-1 text-xl font-semibold text-zinc-950 dark:text-zinc-100">Siapkan caption, carousel, dan video vertikal</h2>
            <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">Satu naskah berita menjadi caption universal, tiga halaman carousel IG dan Facebook, thread X, serta bahan video Shorts dan TikTok.</p>

            <ol class="mt-5 grid gap-2 sm:grid-cols-3" aria-label="Tahap pengerjaan media sosial">
                @foreach ([['caption', '1', 'Caption'], ['carousel', '2', 'Carousel IG + FB'], ['video', '3', 'Shorts + TikTok']] as [$langkah, $nomor, $label])
                    <li>
                        <button type="button" wire:click="pilihLangkahSosmed('{{ $langkah }}')" class="flex min-h-11 w-full items-center gap-3 rounded-lg border px-3 py-2.5 text-left text-sm font-semibold {{ $langkahSosmed === $langkah ? 'border-indigo-300 bg-indigo-50 text-indigo-700 dark:border-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300' : 'border-zinc-200 text-zinc-500 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full {{ $langkahSosmed === $langkah ? 'bg-indigo-600 text-white' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300' }}">{{ $nomor }}</span>
                            {{ $label }}
                        </button>
                    </li>
                @endforeach
            </ol>
            @error('sosmedLangkah') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        @if (session('sosmed-status'))
            <div role="status" class="mx-4 mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200 sm:mx-6">{{ session('sosmed-status') }}</div>
        @endif

        @if ($langkahSosmed === 'caption')
            <div class="grid gap-4 p-4 sm:p-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] lg:items-start">
                <section class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950" aria-labelledby="bahan-caption-title">
                    <h3 id="bahan-caption-title" class="text-sm font-semibold text-zinc-950 dark:text-zinc-100">Naskah sumber</h3>
                    <p class="mt-1 text-xs leading-5 text-zinc-500">Tempel naskah berita final. AI hanya memakai fakta dari naskah ini.</p>
                    <label class="mt-3 block text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                        <span class="sr-only">Naskah berita lengkap</span>
                        <textarea wire:model="bahanSosmed" rows="22" placeholder="Tempel naskah berita lengkap di sini…" class="w-full"></textarea>
                        @error('bahanSosmed') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    @if (! $aiTersedia)
                        <div class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs leading-5 text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-200">AI belum dikonfigurasi. Naskah tetap aman.</div>
                    @endif
                    <button type="button" wire:click="buatCaptionSosmed" wire:loading.attr="disabled" wire:target="buatCaptionSosmed" @disabled(! $aiTersedia) class="mt-4 min-h-11 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading.remove wire:target="buatCaptionSosmed">Buat konten sosmed</span>
                        <span wire:loading wire:target="buatCaptionSosmed">AI sedang menyusun…</span>
                    </button>
                </section>

                <section class="min-w-0" aria-labelledby="hasil-caption-title">
                    <div class="flex items-start justify-between gap-4"><div><h3 id="hasil-caption-title" class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Hasil konten sosmed</h3><p class="mt-1 text-xs leading-5 text-zinc-500">Caption universal, isi carousel, dan thread X siap ditinjau.</p></div>@if ($captionSosmed)<span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">Draf AI</span>@endif</div>
                    <div class="relative mt-3 min-h-[24rem]">
                        <div wire:loading.flex wire:target="buatCaptionSosmed,koreksiCaptionSosmed" class="absolute inset-0 z-10 flex-col justify-center rounded-xl border border-indigo-200 bg-indigo-50/95 p-6 text-center text-sm text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/95 dark:text-indigo-200" role="status" aria-live="polite">
                            <span class="mx-auto flex gap-1" aria-hidden="true"><span class="size-2 animate-pulse rounded-full bg-indigo-500"></span><span class="size-2 animate-pulse rounded-full bg-indigo-500 [animation-delay:150ms]"></span><span class="size-2 animate-pulse rounded-full bg-indigo-500 [animation-delay:300ms]"></span></span>
                            <strong class="mt-3 block font-semibold">AI sedang mengolah konten</strong><span class="mt-1 block text-xs leading-5">Area hasil ini akan diperbarui tanpa menggeser halaman.</span>
                        </div>
                        @if ($captionSosmed)
                            <label class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200"><span class="sr-only">Konten lengkap</span><textarea wire:model="captionSosmed" rows="22" class="w-full leading-7"></textarea>@error('captionSosmed') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror</label>
                        @else
                            <div class="grid min-h-[24rem] place-items-center rounded-xl border border-dashed border-zinc-300 px-6 text-center dark:border-zinc-700"><div><p class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Hasil akan muncul di sini</p><p class="mt-1 text-xs leading-5 text-zinc-500">Masukkan naskah di kiri, lalu pilih Buat konten sosmed.</p></div></div>
                        @endif
                    </div>
                    @error('aiSosmed') <div role="alert" class="mt-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div> @enderror

                    @if ($captionSosmed)
                        <form wire:submit="koreksiCaptionSosmed" class="mt-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950">
                            <label class="block text-sm font-semibold text-zinc-800 dark:text-zinc-200">Instruksi koreksi<span class="mt-1 block text-xs font-normal leading-5 text-zinc-500">Contoh: ringkas caption, perjelas dampak ke masyarakat, dan pertahankan seluruh fakta.</span><textarea wire:model="instruksiKoreksiSosmed" rows="3" placeholder="Tulis bagian yang perlu diperbaiki…" class="w-full"></textarea>@error('instruksiKoreksiSosmed') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror</label>
                            <div class="mt-3 flex justify-end"><button type="submit" wire:loading.attr="disabled" wire:target="koreksiCaptionSosmed" class="min-h-11 rounded-lg border border-indigo-300 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 dark:border-indigo-800 dark:bg-zinc-900 dark:text-indigo-300 dark:hover:bg-zinc-800"><span wire:loading.remove wire:target="koreksiCaptionSosmed">Perbaiki dengan AI</span><span wire:loading wire:target="koreksiCaptionSosmed">AI sedang memperbaiki…</span></button></div>
                        </form>
                        <div class="mt-4 flex justify-end"><button type="button" wire:click="simpanCaptionSosmed" wire:loading.attr="disabled" wire:target="simpanCaptionSosmed" class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="simpanCaptionSosmed">Simpan caption dan lanjut ke carousel</span><span wire:loading wire:target="simpanCaptionSosmed">Menyimpan caption…</span></button></div>
                    @endif
                </section>
            </div>
        @elseif ($langkahSosmed === 'carousel')
            @php($fotoTugas = $this->tugas->bahan->filter(fn ($bahan) => str_starts_with($bahan->mime, 'image/')))
            @php($slideAktif = $carouselSosmedSlides[$carouselSosmedSlideAktif] ?? null)
            @php($slotFotoAktif = $carouselSosmedSlideAktif === 0 ? ($slideAktif['foto_slot_aktif'] ?? 0) : null)
            @php($editorFotoAktif = $carouselSosmedSlideAktif === 0 ? ($slideAktif['foto_slots'][$slotFotoAktif] ?? []) : ($slideAktif ?? []))
            @php($fotoAktif = $slideAktif ? $fotoTugas->firstWhere('id', $editorFotoAktif['bahan_id'] ?? null) : null)
            @php($templateCarouselAktif = $templateCarouselSosmed->firstWhere('id', $carouselSosmedTemplateId))
            @php($backgroundCarouselAktif = $templateCarouselAktif?->aset?->firstWhere('jenis', 'background_slide_'.($carouselSosmedSlideAktif + 1)))
            <div class="space-y-6 p-4 sm:p-6" x-data>
                <div>
                    <h3 class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Editor carousel IG + FB</h3>
                    <p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-500">Teks tiga slide diambil dari hasil AI. Pilih slide, pasangkan foto, lalu sesuaikan teks dan posisi foto sebelum mengunduh ZIP.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between gap-3"><h4 class="text-sm font-semibold">Pilih template</h4><span class="text-xs text-zinc-500">Satu template berlaku untuk 3 slide</span></div>
                    @if ($templateCarouselSosmed->isNotEmpty())
                        <div class="mt-3 flex gap-3 overflow-x-auto pb-2">
                            @foreach ($templateCarouselSosmed as $templateItem)
                                @php($backgroundCover = $templateItem->aset->firstWhere('jenis', 'background_slide_1'))
                                @php($lengkap = $templateItem->aset->whereIn('jenis', ['background_slide_1', 'background_slide_2', 'background_slide_3'])->count() === 3)
                                @if ($lengkap)
                                    <button type="button" wire:click="pilihTemplateCarouselSosmed({{ $templateItem->id }})" wire:key="template-carousel-sosmed-{{ $templateItem->id }}" aria-pressed="{{ $carouselSosmedTemplateId === $templateItem->id ? 'true' : 'false' }}" class="w-32 shrink-0 rounded-xl border p-2 text-left {{ $carouselSosmedTemplateId === $templateItem->id ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100 dark:bg-indigo-950/40 dark:ring-indigo-950' : 'border-zinc-200 hover:border-indigo-300 dark:border-zinc-700' }}">
                                        <img src="{{ route('visual.template.aset', [$templateItem, $backgroundCover]) }}" alt="Preview {{ $templateItem->nama }}" class="aspect-[4/5] w-full rounded-lg object-cover">
                                        <span class="mt-2 block truncate text-xs font-semibold">{{ $templateItem->nama }}</span>
                                        <span class="mt-0.5 block text-[.68rem] text-zinc-500">Versi {{ $templateItem->versi }}</span>
                                    </button>
                                @endif
                            @endforeach
                        </div>
                    @else
                        <div class="mt-3 rounded-xl border border-dashed border-zinc-300 px-5 py-7 text-center dark:border-zinc-700"><p class="text-sm font-semibold">Belum ada template tersedia</p><p class="mt-1 text-xs leading-5 text-zinc-500">Admin perlu mengunggah tiga background lengkap melalui menu Template Visual.</p></div>
                    @endif
                    @error('carouselSosmedTemplateId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($slideAktif)
                    <div class="grid gap-4 lg:grid-cols-[13rem_minmax(0,1fr)_20rem] xl:grid-cols-[14rem_minmax(0,1fr)_22rem]">
                        <aside class="min-w-0 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950" aria-labelledby="foto-carousel-title">
                            <div><h4 id="foto-carousel-title" class="text-sm font-semibold">Daftar foto</h4><p class="mt-1 text-xs leading-5 text-zinc-500">Pilih foto untuk slot yang sedang aktif.</p></div>
                            @if ($carouselSosmedSlideAktif === 0)
                                <div class="mt-3 grid grid-cols-3 gap-1.5 lg:grid-cols-1" aria-label="Pilih slot foto slide 1">
                                    @foreach (['Utama', 'Kanan atas', 'Kanan bawah'] as $slot => $labelSlot)
                                        <button type="button" wire:click="pilihSlotFotoCarouselSosmed({{ $slot }})" aria-pressed="{{ $slotFotoAktif === $slot ? 'true' : 'false' }}" class="min-h-10 rounded-lg border px-2 py-2 text-xs font-semibold {{ $slotFotoAktif === $slot ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200' : 'border-zinc-300 bg-white text-zinc-600 hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' }}">{{ $labelSlot }}</button>
                                    @endforeach
                                </div>
                            @endif
                            @if ($fotoTugas->isNotEmpty())
                                <div class="mt-3 flex gap-2 overflow-x-auto pb-2 lg:max-h-[38rem] lg:flex-col lg:overflow-y-auto lg:pr-1">
                                    @foreach ($fotoTugas as $foto)
                                        @php($dipakai = ($editorFotoAktif['bahan_id'] ?? null) === $foto->id)
                                        <button type="button" wire:click="pilihFotoCarouselSosmed({{ $foto->id }})" wire:key="foto-carousel-{{ $carouselSosmedSlideAktif }}-{{ $foto->id }}" aria-pressed="{{ $dipakai ? 'true' : 'false' }}" class="w-28 shrink-0 rounded-lg border p-1.5 text-left transition-colors lg:w-full {{ $dipakai ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-100 dark:bg-indigo-950/40 dark:ring-indigo-950' : 'border-zinc-200 bg-white hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-900' }}">
                                            <img src="{{ route('tugas.bahan.foto', [$this->tugas, $foto]) }}" alt="{{ $foto->nama_asli }}" class="aspect-[4/3] w-full rounded-md object-cover">
                                            <span class="mt-1.5 block truncate text-xs font-semibold">{{ $foto->nama_asli }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @else
                                <div class="mt-3 rounded-lg border border-dashed border-zinc-300 px-3 py-6 text-center dark:border-zinc-700"><p class="text-xs font-semibold">Belum ada foto</p><p class="mt-1 text-xs leading-5 text-zinc-500">Tambahkan melalui daftar bahan.</p></div>
                            @endif
                            @error("carouselSosmedSlides.{$carouselSosmedSlideAktif}.bahan_id") <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </aside>

                        <section class="min-w-0" aria-labelledby="workarea-carousel-title">
                            <div class="grid grid-cols-3 gap-2" aria-label="Pilih slide carousel">
                                @foreach ($carouselSosmedSlides as $index => $slide)
                                    <button type="button" wire:click="pilihSlideCarouselSosmed({{ $index }})" wire:key="pilih-slide-carousel-{{ $index }}" aria-pressed="{{ $carouselSosmedSlideAktif === $index ? 'true' : 'false' }}" class="min-h-11 rounded-lg border px-2 py-2 text-left text-xs font-semibold sm:px-3 sm:text-sm {{ $carouselSosmedSlideAktif === $index ? 'border-indigo-500 bg-indigo-50 text-indigo-700 ring-2 ring-indigo-100 dark:border-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-950' : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800' }}">
                                        <span class="block">Slide {{ $index + 1 }}</span><span class="mt-0.5 hidden truncate font-normal text-zinc-500 sm:block">{{ $slide['judul'] ?? '' }}</span>
                                    </button>
                                @endforeach
                            </div>
                            <div class="mt-3">
                                <div class="mb-2 flex items-center justify-between gap-3"><h4 id="workarea-carousel-title" class="text-sm font-semibold">Workarea slide {{ $carouselSosmedSlideAktif + 1 }}</h4><span class="text-xs text-zinc-500">1080 × 1350 · PNG</span></div>
                                @if ($carouselSosmedSlideAktif === 0)
                                <div class="relative mx-auto aspect-[4/5] w-full max-w-[32rem] overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 shadow-sm [container-type:inline-size] dark:border-zinc-700">
                                    @if ($backgroundCarouselAktif)<img src="{{ route('visual.template.aset', [$templateCarouselAktif, $backgroundCarouselAktif]) }}" alt="Background template slide 1" class="absolute inset-0 h-full w-full object-cover">@endif
                                    @if (! $backgroundCarouselAktif)
                                    <div class="absolute left-[6.2%] top-[2.5%] z-20 flex items-center gap-[1.4%]"><div class="grid aspect-square w-[6.4%] min-w-7 place-items-center bg-[#12234f] text-[clamp(9px,2vw,15px)] font-semibold text-[#ffca15]">K</div><div class="text-[clamp(7px,1.7vw,12px)] leading-tight text-zinc-950"><strong class="block">KEMENTERIAN HUKUM</strong><span>KANTOR WILAYAH JAWA BARAT</span></div></div>
                                    <div class="absolute left-[18%] top-[7.5%] h-[40%] w-[82%] rounded-tl-[9%] bg-[#ffca15]"></div>
                                    <div class="absolute left-0 top-[17%] h-[41%] w-[88%] rounded-br-[10%] bg-[#172a5d]"></div>
                                    @endif
                                    @foreach ([['left-[5.8%] top-[12.4%] h-[42.4%] w-[43.8%] rounded-[9%]', 0], ['left-[51.8%] top-[12.4%] h-[19.9%] w-[43.7%] rounded-[10%]', 1], ['left-[51.8%] top-[34.5%] h-[20.3%] w-[43.7%] rounded-[10%]', 2]] as [$kelasSlot, $slot])
                                        @php($slotData = $slideAktif['foto_slots'][$slot] ?? [])
                                        @php($fotoSlot = $fotoTugas->firstWhere('id', $slotData['bahan_id'] ?? null))
                                        <button type="button" wire:click="pilihSlotFotoCarouselSosmed({{ $slot }})" class="absolute z-10 overflow-hidden border-2 {{ $kelasSlot }} {{ $slotFotoAktif === $slot ? 'border-indigo-500 ring-2 ring-indigo-200' : 'border-transparent' }}" aria-label="Edit foto {{ ['utama', 'kanan atas', 'kanan bawah'][$slot] }}">
                                            @if ($fotoSlot)<img src="{{ route('tugas.bahan.foto', [$this->tugas, $fotoSlot]) }}" alt="" class="h-full w-full object-cover" style="object-position: {{ $slotData['fokus_x'] ?? 50 }}% {{ $slotData['fokus_y'] ?? 50 }}%; transform: rotate({{ $slotData['rotasi'] ?? 0 }}deg) scale({{ ($slotData['zoom'] ?? 1) * (abs(cos(deg2rad($slotData['rotasi'] ?? 0))) + 1.76 * abs(sin(deg2rad($slotData['rotasi'] ?? 0)))) }}); transform-origin:center">@else<span class="grid h-full place-items-center bg-zinc-200 px-2 text-center text-[clamp(7px,1.8vw,12px)] font-semibold text-zinc-500">Pilih foto</span>@endif
                                        </button>
                                    @endforeach
                                    <div class="absolute left-[5.8%] top-[59.6%] flex h-[6.7%] w-[79.5%] items-center overflow-hidden rounded-full bg-gradient-to-r from-[#fff3c9] to-zinc-300"><strong class="w-[29%] pl-[3%] font-[Roboto] uppercase tracking-tight text-[#172a5d]" style="font-size: {{ ($slideAktif['ukuran_kota'] ?? 35) * 0.125 }}cqw">{{ $slideAktif['kota'] ?? '' }}</strong><span class="flex h-[82%] flex-1 items-center rounded-l-full bg-[#172a5d] pl-[6%] font-[Roboto] font-semibold tracking-tight text-[#ffca15]" style="font-size: {{ ($slideAktif['ukuran_tanggal'] ?? 30) * 0.125 }}cqw">{{ $slideAktif['tanggal'] ?? '' }}</span></div>
                                    <div class="absolute left-[5.8%] right-[5.4%] top-[68.2%] font-[Roboto] text-[#172a5d]"><h5 class="font-semibold leading-[1.02] tracking-[-0.045em]" style="font-size: {{ ($slideAktif['ukuran_judul'] ?? 50) * 0.125 }}cqw">{{ $slideAktif['judul'] ?? '' }}</h5><p class="mt-[3%] font-semibold leading-[1.05] tracking-[-0.03em]" style="font-size: {{ ($slideAktif['ukuran_isi'] ?? 30) * 0.125 }}cqw">{{ $slideAktif['isi'] ?? '' }}</p></div>
                                    @if (! $backgroundCarouselAktif)<div class="absolute bottom-[2.4%] right-0 flex h-[5%] w-[39%] items-center rounded-l-full bg-[#203b8d] pl-[4%] text-[clamp(7px,1.8vw,13px)] italic text-zinc-50">jabar.kemenkum.go.id<span class="absolute right-0 grid h-full w-[24%] place-items-center bg-[#ffca15] text-[#172a5d]">◎</span></div>@endif
                                </div>
                                @else
                                @php($kotakFotoKonten = $penempatanCarouselAktif['foto_slots'][0])
                                @php($kotakTeksKonten = $penempatanCarouselAktif['teks'])
                                <div class="relative mx-auto aspect-[4/5] w-full max-w-[32rem] overflow-hidden rounded-xl border border-zinc-200 bg-[#f7f7f5] shadow-sm [container-type:inline-size] dark:border-zinc-700">
                                    @if ($backgroundCarouselAktif)<img src="{{ route('visual.template.aset', [$templateCarouselAktif, $backgroundCarouselAktif]) }}" alt="Background template slide {{ $carouselSosmedSlideAktif + 1 }}" class="absolute inset-0 h-full w-full object-cover">@endif
                                    <div class="absolute z-10 overflow-hidden bg-zinc-200 dark:bg-zinc-800" style="left:{{ $kotakFotoKonten['x'] / 10.8 }}%;top:{{ $kotakFotoKonten['y'] / 13.5 }}%;width:{{ $kotakFotoKonten['lebar'] / 10.8 }}%;height:{{ $kotakFotoKonten['tinggi'] / 13.5 }}%;border-radius:{{ $kotakFotoKonten['radius'] / 10.8 }}%">
                                        @if ($fotoAktif)
                                            <img src="{{ route('tugas.bahan.foto', [$this->tugas, $fotoAktif]) }}" alt="Preview foto slide {{ $carouselSosmedSlideAktif + 1 }}" class="absolute inset-0 h-full w-full object-cover" style="object-position: {{ $slideAktif['fokus_x'] ?? 50 }}% {{ $slideAktif['fokus_y'] ?? 50 }}%; transform: rotate({{ $slideAktif['rotasi'] ?? 0 }}deg) scale({{ ($slideAktif['zoom'] ?? 1) * (abs(cos(deg2rad($slideAktif['rotasi'] ?? 0))) + 1.59 * abs(sin(deg2rad($slideAktif['rotasi'] ?? 0)))) }}); transform-origin: center">
                                        @else
                                            <div class="flex h-full items-center justify-center px-6 text-center text-sm font-semibold text-zinc-500">Pilih foto untuk slide ini</div>
                                        @endif
                                    </div>
                                    <div class="absolute z-10 overflow-hidden text-[#172a5d]" style="left:{{ $kotakTeksKonten['x'] / 10.8 }}%;top:{{ $kotakTeksKonten['y'] / 13.5 }}%;width:{{ $kotakTeksKonten['lebar'] / 10.8 }}%;height:{{ $kotakTeksKonten['tinggi'] / 13.5 }}%">
                                        <div class="whitespace-pre-line font-[Roboto] leading-[1.18] tracking-[-0.035em]" style="font-size: {{ ($slideAktif['ukuran_isi'] ?? 35) * 0.125 }}cqw">{{ $slideAktif['isi'] ?? '' }}</div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </section>

                        <aside class="space-y-4 rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950" aria-labelledby="alat-carousel-title">
                            <div><h4 id="alat-carousel-title" class="text-sm font-semibold">Edit slide {{ $carouselSosmedSlideAktif + 1 }}</h4><p class="mt-1 text-xs leading-5 text-zinc-500">Perubahan langsung terlihat di workarea.</p></div>
                            @if ($carouselSosmedSlideAktif === 0)
                                <label class="block text-sm font-semibold">Kota<input type="text" wire:model.live.debounce.350ms="carouselSosmedSlides.0.kota" maxlength="30" class="mt-2 w-full" placeholder="BANDUNG"><span class="mt-2 flex items-center justify-between text-xs font-normal text-zinc-500"><span>Ukuran</span><span>{{ $slideAktif['ukuran_kota'] ?? 35 }} pt</span></span><input type="range" wire:model.live="carouselSosmedSlides.0.ukuran_kota" min="10" max="35" step="1" class="mt-1 w-full">@error('carouselSosmedSlides.0.kota') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror</label>
                                <label class="block text-sm font-semibold">Tanggal kegiatan<input type="text" wire:model.live.debounce.350ms="carouselSosmedSlides.0.tanggal" class="mt-2 w-full" placeholder="28 Juli 2026"><span class="mt-2 flex items-center justify-between text-xs font-normal text-zinc-500"><span>Ukuran</span><span>{{ $slideAktif['ukuran_tanggal'] ?? 30 }} pt</span></span><input type="range" wire:model.live="carouselSosmedSlides.0.ukuran_tanggal" min="10" max="30" step="1" class="mt-1 w-full">@error('carouselSosmedSlides.0.tanggal') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror</label>
                                <label class="block text-sm font-semibold">Judul slide<textarea wire:model.live.debounce.350ms="carouselSosmedSlides.0.judul" rows="3" class="mt-2 w-full"></textarea><span class="mt-2 flex items-center justify-between text-xs font-normal text-zinc-500"><span>Ukuran</span><span>{{ $slideAktif['ukuran_judul'] ?? 50 }} pt</span></span><input type="range" wire:model.live="carouselSosmedSlides.0.ukuran_judul" min="10" max="50" step="1" class="mt-1 w-full">@error('carouselSosmedSlides.0.judul') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror</label>
                            @endif
                            <label class="block text-sm font-semibold">{{ $carouselSosmedSlideAktif === 0 ? 'Subjudul' : 'Isi dua paragraf' }}<textarea wire:model.live.debounce.350ms="carouselSosmedSlides.{{ $carouselSosmedSlideAktif }}.isi" rows="6" maxlength="400" class="mt-2 w-full"></textarea><span class="mt-1 flex items-center justify-between text-xs font-normal text-zinc-500"><span>{{ mb_strlen($slideAktif['isi'] ?? '') }}/400</span><span>{{ $slideAktif['ukuran_isi'] ?? ($carouselSosmedSlideAktif === 0 ? 30 : 35) }} pt</span></span><input type="range" wire:model.live="carouselSosmedSlides.{{ $carouselSosmedSlideAktif }}.ukuran_isi" min="{{ $carouselSosmedSlideAktif === 0 ? 10 : 20 }}" max="{{ $carouselSosmedSlideAktif === 0 ? 30 : 35 }}" step="1" class="mt-1 w-full">@error("carouselSosmedSlides.{$carouselSosmedSlideAktif}.isi") <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror</label>
                            <button type="button" wire:click="resetTeksCarouselSosmed" class="min-h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">Reset ukuran teks</button>

                            <div wire:key="kontrol-foto-carousel-{{ $carouselSosmedSlideAktif }}-{{ $slotFotoAktif ?? 'slide' }}" class="border-t border-zinc-200 pt-5 dark:border-zinc-800">
                                <div class="mb-4"><p class="text-sm font-semibold">Atur foto {{ $carouselSosmedSlideAktif === 0 ? ['utama', 'kanan atas', 'kanan bawah'][$slotFotoAktif] : 'slide '.($carouselSosmedSlideAktif + 1) }}</p><p class="mt-1 text-xs text-zinc-500">Setiap pilihan memiliki slider sendiri. Nilai foto lain tidak ikut berubah.</p></div>
                                <div class="space-y-4">
                                    @php($jalurEditor = $carouselSosmedSlideAktif === 0 ? "carouselSosmedSlides.0.foto_slots.{$slotFotoAktif}" : "carouselSosmedSlides.{$carouselSosmedSlideAktif}")
                                    <label class="block text-sm font-semibold">Rotasi <span class="float-right text-xs font-normal text-zinc-500">{{ $editorFotoAktif['rotasi'] ?? 0 }}°</span><input wire:key="rotasi-{{ $jalurEditor }}" type="range" wire:model.live="{{ $jalurEditor }}.rotasi" min="-180" max="180" step="1" class="mt-3 w-full"></label>
                                    <label class="block text-sm font-semibold">Perbesar <span class="float-right text-xs font-normal text-zinc-500">{{ round(($editorFotoAktif['zoom'] ?? 1) * 100) }}%</span><input wire:key="zoom-{{ $jalurEditor }}" type="range" wire:model.live="{{ $jalurEditor }}.zoom" min="1" max="3" step="0.05" class="mt-3 w-full"></label>
                                    <label class="block text-sm font-semibold">Posisi horizontal <span class="float-right text-xs font-normal text-zinc-500">{{ $editorFotoAktif['fokus_x'] ?? 50 }}%</span><input wire:key="fokus-x-{{ $jalurEditor }}" type="range" wire:model.live="{{ $jalurEditor }}.fokus_x" min="0" max="100" step="1" class="mt-3 w-full"></label>
                                    <label class="block text-sm font-semibold">Posisi vertikal <span class="float-right text-xs font-normal text-zinc-500">{{ $editorFotoAktif['fokus_y'] ?? 50 }}%</span><input wire:key="fokus-y-{{ $jalurEditor }}" type="range" wire:model.live="{{ $jalurEditor }}.fokus_y" min="0" max="100" step="1" class="mt-3 w-full"></label>
                                </div>
                                <button type="button" wire:click="resetEditorCarouselSosmed" class="mt-4 min-h-10 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-semibold text-zinc-700 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800">Reset posisi foto</button>
                            </div>
                        </aside>
                    </div>
                @endif

                <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                    <button type="button" wire:click="pilihLangkahSosmed('caption')" class="min-h-11 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Kembali ke caption</button>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        @if ($carouselSosmedTersimpan)<button type="button" wire:click="unduhCarouselSosmed" wire:loading.attr="disabled" wire:target="unduhCarouselSosmed" class="min-h-11 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 disabled:opacity-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200"><span wire:loading.remove wire:target="unduhCarouselSosmed">Unduh ulang ZIP</span><span wire:loading wire:target="unduhCarouselSosmed">Menyiapkan ZIP…</span></button>@endif
                        <button type="button" wire:click="simpanCarouselSosmed" wire:loading.attr="disabled" wire:target="simpanCarouselSosmed" class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="simpanCarouselSosmed">Simpan 3 slide & unduh ZIP</span><span wire:loading wire:target="simpanCarouselSosmed">Merender carousel…</span></button>
                        @if ($carouselSosmedTersimpan)<button type="button" wire:click="pilihLangkahSosmed('video')" class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Lanjut ke video</button>@endif
                    </div>
                </div>
            </div>
        @else
            @php($sceneVideoAktif = $videoSosmedScenes[$videoSosmedSceneAktif] ?? null)
            @php($layoutVideoAktif = $templateVideoSosmedAktif ? \App\Support\PenempatanVideoTemplate::untukTemplate($templateVideoSosmedAktif, $videoSosmedSceneAktif) : null)
            @php($slideVideoAktif = $carouselSosmedSlides[$videoSosmedSceneAktif] ?? [])
            @php($bahanFotoVideoAktif = (int) ($slideVideoAktif['bahan_id'] ?? $slideVideoAktif['foto_slots'][0]['bahan_id'] ?? 0))
            @php($labelGerakanVideo = $this->labelGerakanVideoSosmed())
            @php($totalDurasiVideo = collect($videoSosmedScenes)->sum('durasi') - 1.2)
            <div class="space-y-5 p-4 sm:p-6" x-data="{ sedangPreview: true }">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h3 class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Ubah carousel menjadi video</h3><p class="mt-1 max-w-2xl text-sm leading-6 text-zinc-500">Tiga slide yang sudah disimpan otomatis menjadi video vertikal dengan gerakan halus dan transisi antarhalaman.</p></div><span class="shrink-0 rounded-full bg-zinc-100 px-3 py-1.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">± {{ number_format($totalDurasiVideo, 1, ',', '.') }} detik</span></div>
                @error('videoSosmed') <div role="alert" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div> @enderror

                <div class="grid gap-4 border-y border-zinc-200 py-4 dark:border-zinc-800 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
                    <div><h4 class="text-sm font-semibold">Gaya animasi</h4><p class="mt-1 text-xs leading-5 text-zinc-500">Formal sudah disesuaikan dengan referensi Kemenkum Jabar.</p><div class="mt-3 grid grid-cols-3 gap-2">@foreach (['formal' => 'Formal', 'halus' => 'Halus', 'dinamis' => 'Dinamis'] as $preset => $label)<button type="button" wire:click="terapkanPresetVideoSosmed('{{ $preset }}')" aria-pressed="{{ $videoSosmedPreset === $preset ? 'true' : 'false' }}" class="min-h-11 rounded-lg border px-3 py-2 text-sm font-semibold {{ $videoSosmedPreset === $preset ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200' : 'border-zinc-300 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' }}">{{ $label }}</button>@endforeach</div></div>
                    <div><div class="flex items-center justify-between gap-3"><h4 class="text-sm font-semibold">Template output</h4><span class="text-xs text-zinc-500">1080 × 1920 · MP4</span></div>
                    @if ($templateVideoSosmed->isNotEmpty())
                        <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
                            @foreach ($templateVideoSosmed as $templateItem)
                                @php($jumlahSceneTemplate = $templateItem->layouts->whereIn('jenis', ['video_scene_1', 'video_scene_2', 'video_scene_3'])->count())
                                <button type="button" wire:click="pilihTemplateVideoSosmed({{ $templateItem->id }})" aria-pressed="{{ $videoSosmedTemplateId === $templateItem->id ? 'true' : 'false' }}" class="shrink-0 rounded-lg border px-3 py-2 text-left text-xs {{ $videoSosmedTemplateId === $templateItem->id ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-200' : 'border-zinc-300 dark:border-zinc-700' }}"><strong class="block">{{ $templateItem->nama }}</strong><span class="mt-0.5 block text-zinc-500">v{{ $templateItem->versi }} · {{ $jumlahSceneTemplate }}/3 scene · {{ $templateItem->aset->filter(fn ($aset) => str_starts_with($aset->jenis, 'video_scene_'))->count() }} PNG</span></button>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-2 rounded-lg border border-dashed border-zinc-300 px-4 py-4 text-sm text-zinc-500 dark:border-zinc-700">Admin belum menyediakan template video vertikal aktif.</div>
                    @endif
                    @error('videoSosmedTemplateId') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-[14rem_minmax(0,1fr)_21rem] xl:grid-cols-[15rem_minmax(0,1fr)_22rem]">
                    <aside class="rounded-xl bg-zinc-50 p-3 dark:bg-zinc-950" aria-labelledby="slide-video-title">
                        <h4 id="slide-video-title" class="text-sm font-semibold">Tiga slide carousel</h4><p class="mt-1 text-xs leading-5 text-zinc-500">Pilih halaman untuk melihat dan mengatur gerakannya.</p>
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-2 lg:flex-col lg:overflow-visible">
                            @foreach ($videoSosmedScenes as $index => $scene)
                                <button type="button" wire:click="pilihSceneVideoSosmed({{ $index }})" class="w-28 shrink-0 rounded-lg border p-1.5 text-left {{ $videoSosmedSceneAktif === $index ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950' : 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900' }} lg:w-full">
                                    <img src="{{ route('tugas.carousel.hasil', [$this->tugas, $scene['urutan']]) }}?v={{ $carouselSosmedTersimpan ? 'saved' : 'draft' }}" alt="Slide carousel {{ $scene['urutan'] }}" class="aspect-[4/5] w-full rounded-md object-cover"><span class="mt-1.5 flex items-center justify-between gap-2 text-xs"><strong>Slide {{ $scene['urutan'] }}</strong><span class="text-zinc-500">{{ $scene['durasi'] }}d</span></span>
                                </button>
                            @endforeach
                        </div>
                    </aside>

                    <section class="min-w-0" aria-labelledby="workarea-video-title">
                        <div class="flex items-center justify-between gap-3"><h4 id="workarea-video-title" class="text-sm font-semibold">Preview video</h4><button type="button" x-on:click="sedangPreview = false; requestAnimationFrame(() => sedangPreview = true)" class="min-h-10 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Putar ulang preview</button></div>
                        <div x-bind:class="sedangPreview ? 'proud-template-preview-playing' : ''" class="relative mx-auto mt-2 aspect-[9/16] w-full max-w-[26rem] overflow-hidden rounded-xl bg-zinc-50 shadow-sm dark:bg-zinc-950">
                            @if ($layoutVideoAktif && $templateVideoSosmedAktif)
                                @foreach (collect($layoutVideoAktif['layers'] ?? [])->sortBy('urutan') as $layer)
                                    @php($jenisAsetLayer = 'video_scene_'.($videoSosmedSceneAktif + 1).'_'.$layer['id'])
                                    @php($asetLayer = $templateVideoSosmedAktif->aset->firstWhere('jenis', $jenisAsetLayer))
                                    @php($teksLayer = \App\Support\IsiLayerVideoTemplate::teks($layer, $slideVideoAktif))
                                    @php($styleLayer = 'left:'.((float) $layer['x'] / 10.8).'%;top:'.((float) $layer['y'] / 19.2).'%;width:'.((float) $layer['lebar'] / 10.8).'%;height:'.((float) $layer['tinggi'] / 19.2).'%;z-index:'.(int) $layer['urutan'].';--layer-mulai:'.(float) $layer['mulai'].'s;--layer-durasi:'.max(.01, (float) $layer['durasi_animasi']).'s')
                                    <div data-proud-animation="{{ $layer['animasi'] }}" class="absolute overflow-hidden" style="{{ $styleLayer }}">
                                        @if ($layer['jenis'] === 'png' && $asetLayer)
                                            <img src="{{ route('visual.template.aset', [$templateVideoSosmedAktif, $asetLayer]) }}" alt="{{ $layer['nama'] }}" class="h-full w-full object-contain">
                                        @elseif ($layer['jenis'] === 'foto')
                                            <img src="{{ $bahanFotoVideoAktif > 0 ? route('tugas.bahan.foto', [$this->tugas, $bahanFotoVideoAktif]) : route('tugas.carousel.hasil', [$this->tugas, $sceneVideoAktif['urutan'] ?? 1]) }}?v={{ $videoSosmedSceneAktif }}" alt="Foto scene {{ $sceneVideoAktif['urutan'] ?? 1 }}" class="h-full w-full object-cover">
                                        @elseif (in_array($layer['jenis'], ['judul', 'paragraf'], true))
                                            <div class="flex h-full items-center overflow-hidden whitespace-pre-line px-[3%] font-['Roboto'] leading-tight text-[#172a5d] {{ $layer['jenis'] === 'judul' ? 'text-[clamp(.65rem,2.7vw,2rem)] font-extrabold' : 'text-[clamp(.55rem,2vw,1.35rem)] font-semibold leading-snug' }}">{{ $teksLayer }}</div>
                                        @endif
                                    </div>
                                @endforeach
                            @else
                                <img src="{{ route('tugas.carousel.hasil', [$this->tugas, $sceneVideoAktif['urutan'] ?? 1]) }}?v={{ $videoSosmedSceneAktif }}" alt="Preview slide {{ $sceneVideoAktif['urutan'] ?? 1 }}" class="absolute left-0 top-1/2 w-full -translate-y-1/2">
                            @endif
                        </div>
                            <span class="absolute left-3 top-3 rounded-full bg-zinc-950/70 px-2 py-1 text-xs font-semibold text-white">Slide {{ $sceneVideoAktif['urutan'] ?? 1 }}/3</span><span class="absolute bottom-3 right-3 rounded-full bg-zinc-950/70 px-2 py-1 text-xs font-semibold text-white">{{ $labelGerakanVideo[$sceneVideoAktif['gerakan'] ?? 'zoom_masuk'] }}</span>
                        </div>
                        <p class="mx-auto mt-2 max-w-[26rem] text-center text-xs leading-5 text-zinc-500">Preview menunjukkan gerakan slide aktif. Hasil MP4 menerapkan transisi fade 0,6 detik di antara ketiga slide.</p>
                    </section>

                    <aside class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-950" aria-labelledby="alat-video-title">
                        <h4 id="alat-video-title" class="text-sm font-semibold">Atur slide {{ $sceneVideoAktif['urutan'] ?? 1 }}</h4><p class="mt-1 text-xs leading-5 text-zinc-500">Kontrol hanya berlaku pada halaman yang sedang dipilih.</p>
                        @if ($sceneVideoAktif)
                            <div wire:key="alat-video-slide-{{ $videoSosmedSceneAktif }}" class="mt-4 space-y-5 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                                <label class="block text-sm font-semibold">Gerakan foto dan teks<select wire:model.live="videoSosmedScenes.{{ $videoSosmedSceneAktif }}.gerakan" class="mt-2 w-full rounded-xl border-zinc-300 bg-white text-sm dark:border-zinc-700 dark:bg-zinc-900">@foreach ($labelGerakanVideo as $nilai => $label)<option value="{{ $nilai }}">{{ $label }}</option>@endforeach</select></label>
                                <label class="block text-sm font-semibold">Durasi <span class="float-right text-xs font-normal text-zinc-500">{{ $sceneVideoAktif['durasi'] ?? 7 }} detik</span><input type="range" wire:model.live="videoSosmedScenes.{{ $videoSosmedSceneAktif }}.durasi" min="5" max="12" step="1" class="mt-3 w-full"><span class="mt-1 flex justify-between text-xs font-normal text-zinc-500"><span>5 detik</span><span>12 detik</span></span></label>
                                <div class="rounded-lg border border-zinc-200 bg-white px-3 py-3 text-xs leading-5 text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"><strong class="block text-zinc-900 dark:text-zinc-100">Alur animasi Formal</strong><span class="mt-1 block">Slide masuk lembut, bergerak perlahan, lalu berganti menggunakan cross-fade. Teks tetap menyatu dengan desain carousel.</span></div>
                            </div>
                        @endif
                    </aside>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between"><button type="button" wire:click="pilihLangkahSosmed('carousel')" class="min-h-11 rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">Kembali ke carousel</button><div class="flex flex-col gap-2 sm:flex-row">@if ($videoSosmedStatus === 'selesai' && $videoSosmedPath)<button type="button" wire:click="unduhVideoSosmed" class="min-h-11 rounded-lg border border-indigo-300 bg-white px-4 py-2.5 text-sm font-semibold text-indigo-700 dark:border-indigo-800 dark:bg-zinc-900 dark:text-indigo-300">Unduh MP4</button>@endif<button type="button" wire:click="simpanVideoSosmed" wire:loading.attr="disabled" wire:target="simpanVideoSosmed" @disabled(count($videoSosmedScenes) !== 3 || ! $videoSosmedTemplateId) class="min-h-11 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50"><span wire:loading.remove wire:target="simpanVideoSosmed">Buat video MP4</span><span wire:loading wire:target="simpanVideoSosmed">Merender 3 slide…</span></button></div></div>
            </div>
        @endif
    </section>
    @endif

  </main>
</div>
