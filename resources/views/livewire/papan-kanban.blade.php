<div class="min-h-screen bg-[#eee8dd] text-[#17242b] dark:bg-zinc-950 dark:text-zinc-100">
    <div class="mx-auto max-w-[1700px] px-4 py-6 sm:px-6 lg:px-8">
        <header class="relative overflow-hidden rounded-[2rem] bg-[#102831] px-6 py-8 text-white shadow-2xl shadow-slate-950/15 sm:px-9">
            <div aria-hidden="true" class="absolute -right-24 -top-28 size-96 rounded-full border border-amber-300/15"></div>
            <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-black uppercase tracking-[.24em] text-amber-300">Komando produksi</p>
                    <h1 class="mt-3 font-serif text-4xl leading-none tracking-tight sm:text-5xl">Papan Kanban</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-300">Satu paket, satu posisi. Status di papan ini langsung membaca keadaan produksi—tanpa duplikasi status dari tugas.</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-white/5 px-6 py-4 text-center backdrop-blur">
                    <strong class="block font-serif text-4xl">{{ $totalAktif }}</strong>
                    <span class="text-[.64rem] font-bold uppercase tracking-[.16em] text-slate-400">Paket aktif</span>
                </div>
            </div>
        </header>

        <section class="mt-5 grid gap-3 rounded-3xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
            <label class="text-xs font-bold">Filter PIC
                <select wire:model.live="filterOrang" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">Semua PIC</option>
                    @foreach ($orangFilter as $orang)<option value="{{ $orang->id }}">{{ $orang->nama }}</option>@endforeach
                </select>
            </label>
            <label class="text-xs font-bold">Sumber paket
                <select wire:model.live="filterSumber" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    <option value="">Semua sumber</option>
                    <option value="pr_plan">PR Plan</option>
                    <option value="agenda">Agenda</option>
                    <option value="manual">Manual</option>
                </select>
            </label>
            <a href="{{ route('produksi.index') }}" wire:navigate class="rounded-xl bg-amber-400 px-5 py-2.5 text-center text-xs font-black text-amber-950 hover:bg-amber-300">Buka Meja Produksi →</a>
        </section>

        <div class="mt-6 grid gap-5 xl:grid-cols-3">
            @foreach ([
                ['on_progress', 'On Progress', 'Sedang dikerjakan', 'bg-sky-500'],
                ['finish_production', 'Finish Production', 'Produksi selesai', 'bg-emerald-500'],
                ['review', 'Review', 'Siap dipublikasikan', 'bg-amber-500'],
            ] as [$kunci, $label, $catatan, $warna])
                <section class="min-w-0 rounded-[2rem] border border-stone-300 bg-[#f7f3eb] p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-4 px-1 py-2">
                        <div><div class="flex items-center gap-2"><span class="size-2.5 rounded-full {{ $warna }}"></span><h2 class="text-sm font-black uppercase tracking-[.13em]">{{ $label }}</h2></div><p class="mt-1 pl-[1.15rem] text-xs text-stone-400">{{ $catatan }}</p></div>
                        <strong class="rounded-full bg-white px-3 py-1 text-sm shadow-sm dark:bg-zinc-950">{{ $kolom[$kunci]->count() }}</strong>
                    </div>
                    <div class="mt-3 space-y-3">
                        @forelse ($kolom[$kunci] as $kartu)
                            <article wire:key="paket-kanban-{{ $kartu['id'] }}" class="group rounded-2xl border border-stone-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-950">
                                <div class="flex items-start justify-between gap-3">
                                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-[.62rem] font-black uppercase tracking-[.12em] text-stone-500 dark:bg-zinc-800">{{ $kartu['sumber'] }}</span>
                                    <span class="text-[.62rem] text-stone-400">{{ $kartu['updated_at']->diffForHumans() }}</span>
                                </div>
                                <h3 class="mt-3 text-base font-bold leading-6">{{ $kartu['judul'] }}</h3>
                                @if ($kartu['subjudul'])<p class="mt-1 line-clamp-2 text-xs leading-5 text-stone-500">{{ $kartu['subjudul'] }}</p>@endif

                                @if ($kartu['pic']->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        @foreach ($kartu['pic'] as $pic)
                                            <span class="rounded-full px-2.5 py-1 text-[.65rem] font-bold {{ $pic['butuh_pengganti'] ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' : 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-300' }}">{{ $pic['nama'] }}{{ $pic['peran'] ? ' · '.$pic['peran'] : '' }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-4 flex items-center justify-between gap-3 border-t border-stone-100 pt-3 dark:border-zinc-800">
                                    <div class="text-[.66rem] font-semibold text-stone-400">
                                        @if ($kartu['revisi_ke'] > 0)<span class="text-red-600 dark:text-red-400">Revisi {{ $kartu['revisi_ke'] }}</span>@else<span>Versi awal</span>@endif
                                        @if ($kartu['tenggat'])<span> · {{ $kartu['tenggat']->translatedFormat('j M H:i') }}</span>@endif
                                    </div>
                                    @if ($kunci === 'review')
                                        @can('upload_publikasi')<a href="{{ route('publikasi.index', ['paket' => $kartu['id']]) }}" wire:navigate class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-black text-white hover:bg-emerald-600">Publikasikan →</a>@endcan
                                    @else
                                        @can('kelola_konten')<a href="{{ route('produksi.index', ['paket' => $kartu['id']]) }}" wire:navigate class="rounded-full bg-[#102831] px-3 py-1.5 text-xs font-black text-white hover:bg-amber-700">Kerjakan →</a>@endcan
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="rounded-2xl border border-dashed border-stone-300 px-4 py-10 text-center text-xs leading-5 text-stone-400 dark:border-zinc-700">Tidak ada paket di tahap ini.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
