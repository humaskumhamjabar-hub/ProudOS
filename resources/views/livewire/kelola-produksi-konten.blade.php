<div data-proud-page>
    <div class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 sm:pt-8 lg:px-8">
      <header>
        <div>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs">Ruang kerja editorial</p>
                    <h1 class="mt-4 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Meja Produksi</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">Tarik rencana yang sudah terjadwal, tulis draf tanpa kehilangan versi lama, lalu gerakkan kartu sampai siap ditinjau.</p>
                </div>
                <div class="grid grid-cols-3 divide-x divide-white/10 overflow-hidden rounded-2xl border border-white/10 bg-white/5 backdrop-blur">
                    @foreach ([
                        ['label' => 'Dikerjakan', 'nilai' => $paket->where('status', 'on_progress')->count()],
                        ['label' => 'Selesai produksi', 'nilai' => $paket->where('status', 'finish_production')->count()],
                        ['label' => 'Review', 'nilai' => $paket->where('status', 'review')->count()],
                    ] as $ringkasan)
                        <div class="min-w-24 px-4 py-3 text-center sm:min-w-32">
                            <div class="text-xl font-semibold">{{ $ringkasan['nilai'] }}</div>
                            <div class="mt-0.5 text-[0.62rem] font-bold uppercase tracking-wide text-zinc-500">{{ $ringkasan['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
      </header>
    </div>

    <div class="mx-auto max-w-[1600px] space-y-6 px-5 py-6 sm:px-8 lg:px-10">
        @if (session('produksi-tersimpan'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">✓</span>
                {{ session('produksi-tersimpan') }}
            </div>
        @endif

        @if ($itemSiap->isNotEmpty())
            <section class="rounded-3xl border border-orange-200 bg-orange-50/70 p-5 dark:border-orange-900/60 dark:bg-orange-950/20 sm:p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">Siap ditarik</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight">Antrean dari PR Plan</h2>
                        <p class="mt-1 text-sm text-stone-600 dark:text-zinc-400">Hanya item yang sudah memiliki tanggal resmi di Agenda.</p>
                    </div>
                    <a href="{{ route('pr-plan.index') }}" wire:navigate class="text-sm font-semibold text-orange-700 hover:text-orange-600 dark:text-orange-400">Buka PR Plan →</a>
                </div>
                <div class="mt-5 grid gap-3 lg:grid-cols-2 xl:grid-cols-3">
                    @foreach ($itemSiap as $item)
                        @php
                            $jadwal = $agenda->get($item->agenda_id);
                        @endphp
                        <article wire:key="siap-{{ $item->id }}" class="flex flex-col rounded-2xl border border-orange-200 bg-white p-4 shadow-sm dark:border-orange-900 dark:bg-zinc-900">
                            <div class="flex items-center justify-between gap-3 text-[0.68rem] font-bold uppercase tracking-wide text-stone-500">
                                <span>{{ $item->jenisOutput->nama }}</span>
                                <span>{{ $item->plan->nama }}</span>
                            </div>
                            <h3 class="mt-3 flex-1 font-semibold leading-6">{{ $item->judul }}</h3>
                            @if ($jadwal)
                                <p class="mt-2 text-xs text-stone-500 dark:text-zinc-400">{{ $jadwal->mulai_at->translatedFormat('d M Y · H.i') }} @if ($jadwal->lokasi) · {{ $jadwal->lokasi }} @endif</p>
                            @endif
                            <button wire:click="mulaiDariPrPlan({{ $item->id }})" wire:loading.attr="disabled" class="mt-4 rounded-full bg-orange-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-orange-500 disabled:opacity-50">
                                Mulai produksi
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.75fr)]">
            <section class="min-w-0">
                <div class="mb-4 flex items-end justify-between gap-4 px-1">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-400">Alur aktif</p>
                        <h2 class="mt-1 text-xl font-semibold tracking-tight">Papan produksi</h2>
                    </div>
                    <span class="text-xs text-stone-500">Revisi kembali ke On Progress</span>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach ([
                        'on_progress' => ['nomor' => '01', 'label' => 'On Progress', 'warna' => 'orange'],
                        'finish_production' => ['nomor' => '02', 'label' => 'Finish Production', 'warna' => 'sky'],
                        'review' => ['nomor' => '03', 'label' => 'Review', 'warna' => 'emerald'],
                    ] as $status => $kolom)
                        @php
                            $kartu = $paket->where('status', $status);
                        @endphp
                        <div class="rounded-3xl border border-stone-200 bg-stone-100/70 p-3 dark:border-zinc-800 dark:bg-zinc-900/60">
                            <div class="flex items-center justify-between px-2 py-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="text-xs font-black text-{{ $kolom['warna'] }}-600">{{ $kolom['nomor'] }}</span>
                                    <h3 class="text-sm font-bold uppercase tracking-wide">{{ $kolom['label'] }}</h3>
                                </div>
                                <span class="rounded-full bg-white px-2 py-0.5 text-xs font-bold text-stone-500 shadow-sm dark:bg-zinc-800">{{ $kartu->count() }}</span>
                            </div>
                            <div class="mt-2 space-y-3">
                                @forelse ($kartu as $konten)
                                    @php
                                        $jadwal = $agenda->get($konten->agenda_id);
                                    @endphp
                                    <article wire:key="paket-{{ $konten->id }}" class="rounded-2xl border bg-white p-4 shadow-sm transition {{ $paketAktif?->id === $konten->id ? 'border-orange-400 ring-2 ring-orange-100 dark:border-orange-600 dark:ring-orange-950' : 'border-stone-200 hover:border-stone-300 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-zinc-700' }}">
                                        <button wire:click="pilihPaket({{ $konten->id }})" class="w-full text-left">
                                            <div class="flex items-start justify-between gap-3">
                                                <span class="text-[0.66rem] font-bold uppercase tracking-wide text-stone-400">Paket #{{ $konten->id }}</span>
                                                @if ($konten->revisi_ke > 0)
                                                    <span class="rounded-full bg-red-50 px-2 py-0.5 text-[0.62rem] font-bold uppercase text-red-700 dark:bg-red-950 dark:text-red-300">Revisi {{ $konten->revisi_ke }}</span>
                                                @endif
                                            </div>
                                            <h4 class="mt-2 font-semibold leading-5">{{ $konten->judul }}</h4>
                                            @if ($jadwal)
                                                <p class="mt-2 text-xs leading-5 text-stone-500 dark:text-zinc-400">{{ $jadwal->mulai_at->translatedFormat('d M Y · H.i') }}</p>
                                            @endif
                                            <div class="mt-3 flex items-center justify-between text-[0.68rem] font-semibold text-stone-400">
                                                <span>{{ $konten->draf->count() }} versi draf</span>
                                                <span>Diperbarui {{ $konten->updated_at->diffForHumans() }}</span>
                                            </div>
                                        </button>

                                        <div class="mt-4 flex flex-wrap gap-2 border-t border-stone-100 pt-3 dark:border-zinc-800">
                                            @if ($konten->status === 'on_progress')
                                                <button wire:click="ubahStatus({{ $konten->id }}, 'finish_production')" class="rounded-full bg-sky-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-600">Selesai produksi →</button>
                                            @elseif ($konten->status === 'finish_production')
                                                <button wire:click="ubahStatus({{ $konten->id }}, 'review')" class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600">Kirim review →</button>
                                                <button wire:click="kembalikanRevisi({{ $konten->id }})" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-bold hover:bg-stone-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Revisi</button>
                                            @else
                                                <button wire:click="kembalikanRevisi({{ $konten->id }})" class="rounded-full border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/50 dark:text-red-300">Kembalikan revisi</button>
                                                @can('upload_publikasi')
                                                    <a href="{{ route('publikasi.index', ['paket' => $konten->id]) }}" wire:navigate class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600">Catat publikasi →</a>
                                                @endcan
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-stone-300 px-4 py-8 text-center text-xs leading-5 text-stone-400 dark:border-zinc-700">Belum ada kartu di tahap ini.</div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <aside class="min-w-0">
                <div class="xl:sticky xl:top-6">
                    @if ($paketAktif)
                        <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-xl shadow-stone-950/5 dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="border-b border-stone-200 bg-stone-50 p-5 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                                <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Editor aktif</p>
                                <h2 class="mt-2 text-xl font-semibold leading-7 tracking-tight">{{ $paketAktif->judul }}</h2>
                                <div class="mt-3 flex flex-wrap gap-2 text-xs text-stone-500">
                                    <span class="rounded-full bg-white px-2.5 py-1 shadow-sm dark:bg-zinc-800">{{ str($paketAktif->status)->replace('_', ' ')->title() }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1 shadow-sm dark:bg-zinc-800">Revisi {{ $paketAktif->revisi_ke }}</span>
                                    <span class="rounded-full bg-white px-2.5 py-1 shadow-sm dark:bg-zinc-800">{{ $bahanAktif->count() }} bahan</span>
                                </div>
                                <a href="{{ route('visual.carousel', ['paket' => $paketAktif->id]) }}" wire:navigate class="mt-4 inline-flex items-center rounded-full border border-orange-300 bg-white px-3.5 py-2 text-xs font-bold text-orange-800 hover:bg-orange-50 dark:border-orange-900 dark:bg-zinc-800 dark:text-orange-300">Buka Studio Carousel →</a>
                            </div>

                            <div class="border-b border-stone-200 bg-[#f6f1e7] px-5 py-5 dark:border-zinc-800 dark:bg-zinc-950 sm:px-6">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-sky-700 dark:text-sky-400">Meja bahan</p>
                                        <h3 class="mt-1 font-semibold">Kumpulkan sumber produksi</h3>
                                    </div>
                                    <span class="rounded-full bg-white px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-stone-500 shadow-sm dark:bg-zinc-900">Maks. 20 MB/file</span>
                                </div>

                                <form wire:submit="unggahBahan" class="mt-4 space-y-4">
                                    <fieldset>
                                        <legend class="sr-only">Tipe bahan</legend>
                                        <div class="grid grid-cols-4 gap-1 rounded-xl bg-stone-200/70 p-1 dark:bg-zinc-800">
                                            @foreach (['foto' => 'Foto', 'dokumen' => 'Dokumen', 'catatan' => 'Catatan', 'audio' => 'Audio'] as $nilai => $label)
                                                <label class="cursor-pointer rounded-lg px-2 py-2 text-center text-[0.68rem] font-bold uppercase tracking-wide text-stone-500 transition has-[:checked]:bg-white has-[:checked]:text-stone-950 has-[:checked]:shadow-sm dark:text-zinc-400 dark:has-[:checked]:bg-zinc-950 dark:has-[:checked]:text-white">
                                                    <input wire:model.live="tipeBahan" type="radio" value="{{ $nilai }}" class="sr-only">
                                                    {{ $label }}
                                                </label>
                                            @endforeach
                                        </div>
                                        @error('tipeBahan') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                    </fieldset>

                                    @php
                                        $acceptBahan = match ($tipeBahan) {
                                            'foto' => 'image/jpeg,image/png,image/webp',
                                            'dokumen' => '.pdf,.doc,.docx,.txt',
                                            default => 'audio/mpeg,audio/wav,audio/x-m4a,audio/ogg',
                                        };
                                    @endphp
                                    @if ($tipeBahan === 'catatan')
                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold">Catatan sumber</span>
                                            <textarea wire:model="catatanBahan" rows="4" placeholder="Pesan utama, fakta lapangan, kutipan, atau konteks yang harus masuk…" class="w-full rounded-xl border-stone-300 bg-white text-sm leading-6 text-stone-950 focus:border-sky-600 focus:ring-sky-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"></textarea>
                                            @error('catatanBahan') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                        </label>
                                    @else
                                        <label class="block">
                                            <span class="mb-1.5 block text-sm font-semibold">Pilih {{ $tipeBahan }}</span>
                                            <input wire:model="unggahanBahan" type="file" multiple accept="{{ $acceptBahan }}"
                                                class="block w-full rounded-xl border border-stone-300 bg-white text-xs file:mr-3 file:border-0 file:bg-sky-700 file:px-3 file:py-2.5 file:font-bold file:text-white dark:border-zinc-700 dark:bg-zinc-900">
                                            <p class="mt-1 text-xs text-stone-500">Bisa pilih beberapa file sekaligus, maksimal 20 file.</p>
                                            @error('unggahanBahan') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                            @error('unggahanBahan.*') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                        </label>
                                    @endif

                                    <div class="flex items-center justify-between gap-4">
                                        <span wire:loading wire:target="unggahanBahan,unggahBahan" class="text-xs font-semibold text-sky-700 dark:text-sky-400">Mengunggah bahan…</span>
                                        <span wire:loading.remove wire:target="unggahanBahan,unggahBahan" class="text-xs text-stone-500">Disimpan privat di paket ini.</span>
                                        <button type="submit" wire:loading.attr="disabled" class="rounded-full bg-sky-700 px-4 py-2 text-xs font-bold text-white hover:bg-sky-600 disabled:opacity-50">Tambahkan bahan</button>
                                    </div>
                                </form>
                            </div>

                            @if ($bahanAktif->isNotEmpty())
                                <div class="border-b border-stone-200 px-5 py-5 dark:border-zinc-800 sm:px-6">
                                    <div class="flex items-end justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Rak sumber</p>
                                            <h3 class="mt-1 font-semibold">{{ $bahanAktif->count() }} bahan tersimpan</h3>
                                        </div>
                                        <span class="text-[0.65rem] font-semibold text-stone-400">↑↓ atur urutan</span>
                                    </div>
                                    <div class="mt-4 space-y-2">
                                        @foreach ($bahanAktif as $bahan)
                                            @php
                                                $ikonBahan = match ($bahan->tipe) { 'foto' => '▣', 'dokumen' => '▤', 'audio' => '◉', default => '✎' };
                                                $statusEkstraksi = match ($bahan->status_ekstraksi) { 'selesai' => 'Teks siap', 'gagal' => 'Ekstraksi gagal', default => $bahan->tipe === 'foto' ? 'Foto sumber' : 'Menunggu ekstraksi' };
                                            @endphp
                                            <article wire:key="bahan-{{ $bahan->id }}" class="group rounded-2xl border border-stone-200 bg-stone-50 p-3 dark:border-zinc-800 dark:bg-zinc-950">
                                                <div class="flex items-start gap-3">
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $bahan->dipakai_final ? 'bg-emerald-600 text-white' : 'bg-white text-sky-700 shadow-sm dark:bg-zinc-900 dark:text-sky-400' }}">{{ $ikonBahan }}</div>
                                                    <div class="min-w-0 flex-1">
                                                        <div class="flex items-start justify-between gap-3">
                                                            <div class="min-w-0">
                                                                <p class="truncate text-sm font-semibold">{{ $bahan->nama_asli }}</p>
                                                                <p class="mt-0.5 text-[0.68rem] font-medium text-stone-400">{{ str($bahan->tipe)->title() }} · {{ $statusEkstraksi }}</p>
                                                            </div>
                                                            <span class="text-[0.65rem] font-bold text-stone-300">{{ str_pad($bahan->urutan, 2, '0', STR_PAD_LEFT) }}</span>
                                                        </div>
                                                        @if ($bahan->tipe === 'catatan')
                                                            <p class="mt-2 line-clamp-2 text-xs leading-5 text-stone-600 dark:text-zinc-400">{{ $bahan->teks_terekstrak }}</p>
                                                        @elseif ($bahan->tipe === 'dokumen' && $bahan->status_ekstraksi === 'selesai')
                                                            <details class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50/70 p-2.5 dark:border-emerald-900 dark:bg-emerald-950/20">
                                                                <summary class="cursor-pointer text-xs font-bold text-emerald-700 dark:text-emerald-400">Lihat teks hasil ekstraksi</summary>
                                                                <p class="mt-2 max-h-40 overflow-y-auto whitespace-pre-line text-xs leading-5 text-stone-600 dark:text-zinc-400">{{ $bahan->teks_terekstrak }}</p>
                                                            </details>
                                                        @endif
                                                        <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                                            <button type="button" wire:click="pindahBahan({{ $bahan->id }}, 'naik')" aria-label="Naikkan urutan {{ $bahan->nama_asli }}" class="rounded-full border border-stone-300 px-2.5 py-1 text-xs font-bold hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-900">↑</button>
                                                            <button type="button" wire:click="pindahBahan({{ $bahan->id }}, 'turun')" aria-label="Turunkan urutan {{ $bahan->nama_asli }}" class="rounded-full border border-stone-300 px-2.5 py-1 text-xs font-bold hover:bg-white dark:border-zinc-700 dark:hover:bg-zinc-900">↓</button>
                                                            @if ($bahan->tipe === 'foto')
                                                                <button type="button" wire:click="toggleDipakaiFinal({{ $bahan->id }})" class="rounded-full px-2.5 py-1 text-[0.68rem] font-bold {{ $bahan->dipakai_final ? 'bg-emerald-600 text-white' : 'border border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-400 dark:hover:bg-emerald-950' }}">{{ $bahan->dipakai_final ? 'Dipakai final ✓' : 'Tandai final' }}</button>
                                                            @elseif ($bahan->tipe === 'dokumen' && $bahan->status_ekstraksi === 'gagal')
                                                                <button type="button" wire:click="cobaUlangEkstraksi({{ $bahan->id }})" wire:loading.attr="disabled" class="rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[0.68rem] font-bold text-red-700 hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">Coba ekstrak ulang</button>
                                                            @endif
                                                            <button type="button" wire:click="hapusBahan({{ $bahan->id }})" wire:confirm="Hapus bahan ini dari paket produksi?" class="ml-auto rounded-full px-2.5 py-1 text-[0.68rem] font-bold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950">Hapus</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </article>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <section class="relative overflow-hidden border-b border-stone-200 bg-[#101d25] px-5 py-6 text-white dark:border-zinc-800 sm:px-6">
                                <div aria-hidden="true" class="absolute -right-10 -top-12 h-40 w-40 rounded-full border border-cyan-300/15"></div>
                                <div aria-hidden="true" class="absolute right-3 top-1 h-24 w-24 rounded-full bg-cyan-400/10 blur-2xl"></div>
                                <div class="relative">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">Ruang bantu AI</p>
                                            <h3 class="mt-1 text-lg font-semibold">Usulan, bukan keputusan</h3>
                                            <p class="mt-1 max-w-sm text-xs leading-5 text-slate-300">AI membaca teks sumber. Hasilnya selalu masuk antrean tinjau dan tidak pernah menjadi draf otomatis.</p>
                                        </div>
                                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide {{ $aiTersedia ? 'border-emerald-300/30 bg-emerald-400/10 text-emerald-200' : 'border-amber-300/30 bg-amber-400/10 text-amber-200' }}">
                                            {{ $aiTersedia ? 'Siap' : 'Belum aktif' }}
                                        </span>
                                    </div>

                                    <form wire:submit="buatUsulanAi" class="mt-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
                                        <label>
                                            <span class="sr-only">Jenis usulan AI</span>
                                            <select wire:model="jenisUsulanAi" class="w-full rounded-xl border-white/15 bg-white/10 text-sm text-white focus:border-cyan-300 focus:ring-cyan-300">
                                                <option class="text-stone-950" value="fakta">Ekstrak fakta</option>
                                                <option class="text-stone-950" value="berita">Usulan berita</option>
                                                <option class="text-stone-950" value="caption">Usulan caption</option>
                                                <option class="text-stone-950" value="opsi_judul">Opsi judul</option>
                                                <option class="text-stone-950" value="ringkasan">Ringkasan sumber</option>
                                            </select>
                                        </label>
                                        <button type="submit" wire:loading.attr="disabled" wire:target="buatUsulanAi" class="rounded-xl bg-cyan-300 px-4 py-2.5 text-xs font-black text-slate-950 transition hover:bg-white disabled:cursor-not-allowed disabled:opacity-50">
                                            <span wire:loading.remove wire:target="buatUsulanAi">Buat usulan ↗</span>
                                            <span wire:loading wire:target="buatUsulanAi">Membaca sumber…</span>
                                        </button>
                                    </form>
                                    @error('jenisUsulanAi') <span class="mt-2 block text-xs text-red-300">{{ $message }}</span> @enderror
                                    @error('ai') <div class="mt-3 rounded-xl border border-amber-300/20 bg-amber-300/10 px-3 py-2.5 text-xs leading-5 text-amber-100">{{ $message }}</div> @enderror

                                    @if ($usulanAiAktif->isNotEmpty())
                                        <div class="mt-5 space-y-3">
                                            @foreach ($usulanAiAktif as $usulan)
                                                @php
                                                    $warnaStatus = match ($usulan->status) {
                                                        'diterima' => 'bg-emerald-300/15 text-emerald-200',
                                                        'ditolak' => 'bg-red-300/15 text-red-200',
                                                        'diedit' => 'bg-sky-300/15 text-sky-200',
                                                        default => 'bg-amber-300/15 text-amber-100',
                                                    };
                                                @endphp
                                                <article wire:key="usulan-ai-{{ $usulan->id }}" class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <div class="flex min-w-0 flex-wrap items-center gap-2">
                                                            <span class="text-[0.68rem] font-black uppercase tracking-[0.13em] text-cyan-300">{{ str($usulan->jenis)->replace('_', ' ') }}</span>
                                                            <span class="rounded-full px-2 py-0.5 text-[0.62rem] font-bold {{ $warnaStatus }}">{{ str($usulan->status)->title() }}</span>
                                                        </div>
                                                        <span class="shrink-0 text-[0.62rem] text-slate-400">{{ $usulan->created_at->diffForHumans() }}</span>
                                                    </div>

                                                    @if ($usulanAiDieditId === $usulan->id)
                                                        <div class="mt-3">
                                                            <textarea wire:model="isiEditUsulanAi" rows="7" class="w-full rounded-xl border-cyan-300/30 bg-slate-950/70 text-sm leading-6 text-white focus:border-cyan-300 focus:ring-cyan-300"></textarea>
                                                            @error('isiEditUsulanAi') <span class="mt-1 block text-xs text-red-300">{{ $message }}</span> @enderror
                                                            <div class="mt-2 flex justify-end gap-2">
                                                                <button type="button" wire:click="batalEditUsulanAi" class="rounded-full border border-white/15 px-3 py-1.5 text-xs font-bold text-slate-300 hover:bg-white/10">Batal</button>
                                                                <button type="button" wire:click="simpanEditUsulanAi" class="rounded-full bg-cyan-300 px-3 py-1.5 text-xs font-black text-slate-950 hover:bg-white">Simpan koreksi</button>
                                                            </div>
                                                        </div>
                                                    @else
                                                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-200">{{ $usulan->isi }}</p>
                                                        <div class="mt-3 flex flex-wrap items-center gap-2">
                                                            @if ($usulan->status === 'menunggu')
                                                                <button type="button" wire:click="terimaUsulanAi({{ $usulan->id }})" class="rounded-full bg-emerald-300 px-3 py-1.5 text-xs font-black text-emerald-950 hover:bg-white">Terima</button>
                                                                <button type="button" wire:click="mulaiEditUsulanAi({{ $usulan->id }})" class="rounded-full border border-cyan-300/30 px-3 py-1.5 text-xs font-bold text-cyan-200 hover:bg-cyan-300/10">Edit dulu</button>
                                                                <button type="button" wire:click="tolakUsulanAi({{ $usulan->id }})" class="rounded-full px-3 py-1.5 text-xs font-bold text-red-200 hover:bg-red-300/10">Tolak</button>
                                                            @elseif (in_array($usulan->status, ['diterima', 'diedit'], true))
                                                                <button type="button" wire:click="gunakanUsulanAi({{ $usulan->id }})" class="rounded-full bg-white px-3 py-1.5 text-xs font-black text-slate-950 hover:bg-cyan-100">Salin ke editor ↓</button>
                                                            @endif
                                                            @if ($usulan->model)
                                                                <span class="ml-auto text-[0.62rem] text-slate-500">{{ $usulan->model }} · {{ $usulan->prompt_versi }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </article>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="mt-5 rounded-2xl border border-dashed border-white/15 px-4 py-5 text-center text-xs leading-5 text-slate-400">Belum ada usulan. Siapkan catatan atau dokumen dengan teks siap, lalu pilih jenis bantuan.</div>
                                    @endif
                                </div>
                            </section>

                            <form wire:submit="simpanDraf" class="space-y-5 p-5 sm:p-6">
                                <label class="block">
                                    <span class="mb-1.5 block text-sm font-semibold">Jenis naskah</span>
                                    <select wire:model="jenisDraf" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        <option value="berita">Berita</option>
                                        <option value="caption">Caption</option>
                                        <option value="judul">Judul</option>
                                        <option value="script">Script</option>
                                    </select>
                                    @error('jenisDraf') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="block">
                                    <span class="mb-1.5 flex items-center justify-between gap-3 text-sm font-semibold">
                                        Isi draf
                                        <span class="font-normal text-stone-400">{{ str($isiDraf)->length() }} karakter</span>
                                    </span>
                                    <textarea wire:model="isiDraf" rows="13" placeholder="Mulai tulis naskah di sini…" class="w-full resize-y rounded-2xl border-stone-300 bg-stone-50 text-sm leading-7 text-stone-950 focus:border-orange-500 focus:bg-white focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"></textarea>
                                    @error('isiDraf') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <div class="flex items-center justify-between gap-4">
                                    <p class="max-w-44 text-xs leading-5 text-stone-500">Setiap simpan membuat versi baru. Riwayat tidak ditimpa.</p>
                                    <button type="submit" wire:loading.attr="disabled" class="rounded-full bg-stone-950 px-5 py-2.5 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-50 dark:bg-orange-500 dark:text-zinc-950 dark:hover:bg-orange-400">Simpan versi baru</button>
                                </div>
                            </form>

                            @if ($drafAktif->isNotEmpty())
                                <div class="border-t border-stone-200 px-5 py-5 dark:border-zinc-800 sm:px-6">
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Riwayat versi</p>
                                    <div class="mt-3 space-y-2">
                                        @foreach ($drafAktif->take(5) as $draf)
                                            <button type="button" wire:click="muatDraf({{ $draf->id }})" class="flex w-full items-center justify-between gap-3 rounded-xl border border-stone-200 px-3 py-2 text-left text-xs hover:border-orange-300 hover:bg-orange-50 dark:border-zinc-800 dark:hover:border-orange-900 dark:hover:bg-orange-950/20">
                                                <span class="font-semibold">{{ str($draf->jenis)->title() }} · v{{ $draf->versi }}</span>
                                                <span class="text-stone-400">{{ $draf->created_at->diffForHumans() }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </section>
                    @else
                        <section class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-16 text-center dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-xl text-orange-700 dark:bg-orange-950 dark:text-orange-300">✎</div>
                            <h2 class="mt-4 font-semibold">Belum ada paket aktif</h2>
                            <p class="mt-1 text-sm leading-6 text-stone-500">Mulai produksi dari antrean PR Plan, lalu editor draf akan terbuka di sini.</p>
                        </section>
                    @endif
                </div>
            </aside>
        </div>
    </div>
</div>
