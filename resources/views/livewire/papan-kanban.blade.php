<div data-proud-page>
    <main class="space-y-6">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p>Alur produksi</p>
                <h1>Papan Kanban</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6">Satu paket, satu posisi. Status langsung membaca keadaan produksi tanpa menduplikasi status tugas.</p>
            </div>
            <div class="flex items-center gap-3 rounded-xl border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-950">
                <strong class="text-2xl font-semibold text-zinc-950 dark:text-zinc-100">{{ $totalAktif }}</strong>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">paket aktif</span>
            </div>
        </header>

        <section aria-label="Filter papan" class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end sm:p-5">
            <label class="text-sm font-semibold text-zinc-950 dark:text-zinc-100">Filter PIC
                <select wire:model.live="filterOrang" class="mt-1.5 min-h-11 w-full rounded-xl border-zinc-300 bg-white text-sm text-zinc-950 focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Semua PIC</option>
                    @foreach ($orangFilter as $orang)<option value="{{ $orang->id }}">{{ $orang->nama }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-zinc-950 dark:text-zinc-100">Sumber paket
                <select wire:model.live="filterSumber" class="mt-1.5 min-h-11 w-full rounded-xl border-zinc-300 bg-white text-sm text-zinc-950 focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                    <option value="">Semua sumber</option>
                    <option value="pr_plan">PR Plan</option>
                    <option value="agenda">Agenda</option>
                    <option value="manual">Manual</option>
                </select>
            </label>
            <a href="{{ route('produksi.index') }}" wire:navigate class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-indigo-700">Buka Meja Produksi</a>
        </section>

        <div class="grid gap-5 xl:grid-cols-3">
            @foreach ([
                ['on_progress', 'On Progress', 'Sedang dikerjakan', 'bg-sky-600'],
                ['finish_production', 'Finish Production', 'Produksi selesai', 'bg-emerald-600'],
                ['review', 'Review', 'Siap dipublikasikan', 'bg-amber-500'],
            ] as [$kunci, $label, $catatan, $warna])
                <section aria-labelledby="kolom-{{ $kunci }}" class="min-w-0 overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-start justify-between gap-4 border-b border-zinc-200 px-4 py-4 dark:border-zinc-800">
                        <div>
                            <div class="flex items-center gap-2">
                                <span aria-hidden="true" class="size-2.5 rounded-full {{ $warna }}"></span>
                                <h2 id="kolom-{{ $kunci }}" class="text-sm font-semibold text-zinc-950 dark:text-zinc-100">{{ $label }}</h2>
                            </div>
                            <p class="mt-1 pl-[1.125rem] text-xs text-zinc-500 dark:text-zinc-400">{{ $catatan }}</p>
                        </div>
                        <span class="inline-flex min-w-7 justify-center rounded-full border border-zinc-200 bg-white px-2.5 py-1 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-300">{{ $kolom[$kunci]->count() }}</span>
                    </div>

                    <div class="space-y-3 p-3">
                        @forelse ($kolom[$kunci] as $kartu)
                            <article wire:key="paket-kanban-{{ $kartu['id'] }}" class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition-colors hover:border-indigo-300 dark:border-zinc-800 dark:bg-zinc-950 dark:hover:border-indigo-700">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="rounded-full border border-zinc-200 bg-zinc-50 px-2.5 py-1 text-xs font-semibold text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">{{ $kartu['sumber'] }}</span>
                                    <time class="text-xs text-zinc-500 dark:text-zinc-400">{{ $kartu['updated_at']->diffForHumans() }}</time>
                                </div>

                                <h3 class="mt-3 text-base font-semibold leading-6 text-zinc-950 dark:text-zinc-100">{{ $kartu['judul'] }}</h3>
                                @if ($kartu['subjudul'])<p class="mt-1 line-clamp-2 text-sm leading-5 text-zinc-500 dark:text-zinc-400">{{ $kartu['subjudul'] }}</p>@endif

                                @if ($kartu['pic']->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-1.5" aria-label="PIC paket">
                                        @foreach ($kartu['pic'] as $pic)
                                            <span class="rounded-full border px-2.5 py-1 text-xs font-semibold {{ $pic['butuh_pengganti'] ? 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200' : 'border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200' }}">{{ $pic['nama'] }}{{ $pic['peran'] ? ' · '.$pic['peran'] : '' }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-4 flex flex-col gap-3 border-t border-zinc-200 pt-3 dark:border-zinc-800 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                        @if ($kartu['revisi_ke'] > 0)<span class="text-red-600 dark:text-red-300">Revisi {{ $kartu['revisi_ke'] }}</span>@else<span>Versi awal</span>@endif
                                        @if ($kartu['tenggat'])<span> · {{ $kartu['tenggat']->translatedFormat('j M H:i') }}</span>@endif
                                    </div>
                                    @if ($kunci === 'review')
                                        @can('upload_publikasi')<a href="{{ route('publikasi.index', ['paket' => $kartu['id']]) }}" wire:navigate class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-indigo-700">Publikasikan</a>@endcan
                                    @else
                                        @can('kelola_konten')<a href="{{ route('produksi.index', ['paket' => $kartu['id']]) }}" wire:navigate class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 py-2 text-xs font-semibold text-zinc-950 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">Kerjakan</a>@endcan
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-zinc-300 bg-white/60 px-4 py-10 text-center dark:border-zinc-700 dark:bg-zinc-950/40">
                                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Belum ada paket di tahap ini.</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Paket akan muncul saat status produksinya berubah.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </main>
</div>
