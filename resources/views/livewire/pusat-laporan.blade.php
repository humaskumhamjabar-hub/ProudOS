<div data-proud-page>
    <div class="mx-auto max-w-[1500px] px-4 py-6 sm:px-6 lg:px-8">
        <header class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white px-6 py-6 text-zinc-950 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100 sm:px-8">
            <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[.16em] text-indigo-600 dark:text-indigo-400">Sumber kebenaran PROUD</p>
                    <h1>Pusat Laporan</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-zinc-500 dark:text-zinc-400">Data operasional langsung diringkas menjadi laporan publikasi, evaluasi PR Plan, dan rekap pembelajaran magang. Ekspor selalu satu arah dari PROUD.</p>
                </div>
                <div class="grid grid-cols-3 divide-x divide-zinc-200 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:divide-zinc-700 dark:border-zinc-700 dark:bg-zinc-950">
                    <div class="px-5 py-3 text-center"><strong class="block text-2xl">{{ $ringkasanPublikasi['total'] }}</strong><span class="text-[.62rem] uppercase tracking-wider text-zinc-500">Tayang</span></div>
                    <div class="px-5 py-3 text-center"><strong class="block text-2xl">{{ $ringkasanPublikasi['pic'] }}</strong><span class="text-[.62rem] uppercase tracking-wider text-zinc-500">PIC</span></div>
                    <div class="px-5 py-3 text-center"><strong class="block text-2xl">{{ count($ringkasanPublikasi['kanal']) }}</strong><span class="text-[.62rem] uppercase tracking-wider text-zinc-500">Kanal</span></div>
                </div>
            </div>
        </header>

        <nav class="mt-5 flex gap-1 overflow-x-auto rounded-2xl border border-stone-300 bg-white p-1.5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900" aria-label="Jenis laporan">
            @foreach (['publikasi' => 'Laporan Publikasi', 'pr-plan' => 'Evaluasi PR Plan', 'magang' => 'Rekap Magang'] as $nilai => $label)
                <button type="button" wire:click="pilihTab('{{ $nilai }}')" class="min-w-fit flex-1 rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors {{ $tab === $nilai ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-400' : 'text-zinc-500 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800' }}">{{ $label }}</button>
            @endforeach
        </nav>

        @if ($tab === 'publikasi')
            <section class="mt-6">
                <div class="grid gap-3 rounded-3xl border border-stone-300 bg-white p-4 shadow-lg shadow-stone-950/5 dark:border-zinc-800 dark:bg-zinc-900 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1.2fr_auto] lg:items-end">
                    <label class="text-xs font-bold">Mulai
                        <input type="date" wire:model.live="mulai" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                    <label class="text-xs font-bold">Selesai
                        <input type="date" wire:model.live="selesai" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </label>
                    <label class="text-xs font-bold">Kanal
                        <select wire:model.live="kanalId" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Semua kanal</option>
                            @foreach ($kanal as $item)<option value="{{ $item->id }}">{{ $item->nama }}</option>@endforeach
                        </select>
                    </label>
                    <div class="flex flex-wrap gap-2 sm:col-span-2 lg:col-span-1"><button wire:click="unduhExcelPublikasi" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700">Excel</button><button wire:click="unduhPdfPublikasi" class="rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-xs font-semibold text-zinc-950 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">PDF</button><button wire:click="unduhCsvPublikasi" class="rounded-lg border border-zinc-300 bg-white px-4 py-2.5 text-xs font-semibold text-zinc-950 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">CSV</button></div>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ([['Total publikasi', $ringkasanPublikasi['total'], 'Keluaran pada periode terpilih'], ['PIC aktif', $ringkasanPublikasi['pic'], 'Orang yang mencatat publikasi'], ['Kanal terpakai', count($ringkasanPublikasi['kanal']), 'Sebaran kanal tujuan'], ['Revisi pascatayang', $ringkasanPublikasi['revisi_tayang'], 'Perubahan yang tercatat audit']] as [$judul, $angka, $catatan])
                        <article class="rounded-3xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                            <p class="text-[.68rem] font-black uppercase tracking-[.16em] text-stone-400">{{ $judul }}</p>
                            <strong class="mt-2 block text-4xl font-semibold">{{ $angka }}</strong>
                            <p class="mt-1 text-xs leading-5 text-stone-500">{{ $catatan }}</p>
                        </article>
                    @endforeach
                </div>

                <div class="mt-4 overflow-hidden rounded-3xl border border-stone-300 bg-white shadow-lg shadow-stone-950/5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[840px] text-left text-sm">
                            <thead class="bg-zinc-100 text-[.66rem] uppercase tracking-[.14em] text-zinc-500 dark:bg-zinc-950 dark:text-zinc-400"><tr><th class="px-5 py-3">Tayang</th><th class="px-5 py-3">Konten</th><th class="px-5 py-3">Kanal</th><th class="px-5 py-3">PIC</th><th class="px-5 py-3">Jejak</th></tr></thead>
                            <tbody class="divide-y divide-stone-200 dark:divide-zinc-800">
                                @forelse ($publikasi as $item)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                                        <td class="px-5 py-4"><span class="font-bold">{{ $item->tayang_at->format('d/m/Y') }}</span><span class="block text-xs text-stone-400">{{ $item->tayang_at->format('H:i') }}</span></td>
                                        <td class="max-w-sm px-5 py-4"><p class="font-semibold">{{ $item->judul_paket }}</p><a href="{{ $item->url }}" target="_blank" class="mt-1 block truncate text-xs text-sky-700 hover:underline dark:text-sky-400">{{ $item->url }}</a></td>
                                        <td class="px-5 py-4"><span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-700 dark:bg-sky-950 dark:text-sky-300">{{ $item->kanal?->nama }}</span></td>
                                        <td class="px-5 py-4">{{ $item->pic_nama }}</td>
                                        <td class="px-5 py-4">@if ($item->diubah_setelah_tayang)<span class="text-xs font-bold text-amber-700 dark:text-amber-400">Direvisi</span>@else<span class="text-xs text-stone-400">Utuh</span>@endif</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-12 text-center text-stone-400">Tidak ada publikasi pada filter ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @elseif ($tab === 'pr-plan')
            <section class="mt-6">
                <div class="rounded-3xl border border-stone-300 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <label class="block max-w-xl text-xs font-bold">PR Plan yang dievaluasi
                        <select wire:model.live="prPlanId" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            @foreach ($prPlans as $plan)<option value="{{ $plan->id }}">{{ $plan->nama }} · {{ $plan->periode_mulai->format('M Y') }}</option>@endforeach
                        </select>
                    </label>
                </div>
                @if ($evaluasiPrPlan['plan'])
                    <div class="mt-4 grid gap-5 xl:grid-cols-[.9fr_1.1fr]">
                        <article class="overflow-hidden rounded-[2rem] border border-zinc-200 bg-white p-7 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                            <p class="text-xs font-semibold uppercase tracking-[.2em] text-indigo-600 dark:text-indigo-400">Capaian aktual</p>
                            <div class="mt-6 flex items-end gap-4"><strong class="text-7xl font-semibold leading-none">{{ $evaluasiPrPlan['persentase'] }}%</strong><span class="pb-2 text-sm text-zinc-500 dark:text-zinc-400">dari target periode</span></div>
                            <div class="mt-7 h-3 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-indigo-600 dark:bg-indigo-500" style="width: {{ min(100, $evaluasiPrPlan['persentase']) }}%"></div></div>
                            <div class="mt-6 grid grid-cols-3 divide-x divide-zinc-200 text-center dark:divide-zinc-700"><div><strong class="text-2xl">{{ $evaluasiPrPlan['target'] }}</strong><span class="block text-[.62rem] uppercase text-zinc-500 dark:text-zinc-400">Target</span></div><div><strong class="text-2xl">{{ $evaluasiPrPlan['realisasi'] }}</strong><span class="block text-[.62rem] uppercase text-zinc-500 dark:text-zinc-400">Tayang</span></div><div><strong class="text-2xl">{{ $evaluasiPrPlan['kekurangan'] }}</strong><span class="block text-[.62rem] uppercase text-zinc-500 dark:text-zinc-400">Kurang</span></div></div>
                        </article>
                        <div class="grid gap-4 sm:grid-cols-3">
                            @foreach ([['Masih di pipeline', $evaluasiPrPlan['pipeline'], 'Sudah menjadi paket, belum tayang', 'text-sky-700'], ['Belum dikerjakan', $evaluasiPrPlan['belum_dikerjakan'], 'Masih berupa ide di antrean', 'text-amber-700'], ['Dibatalkan', $evaluasiPrPlan['batal'], 'Tidak dihitung sebagai realisasi', 'text-red-700']] as [$judul, $angka, $catatan, $warna])
                                <article class="rounded-3xl border border-stone-300 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900"><p class="text-xs font-bold text-stone-500">{{ $judul }}</p><strong class="mt-4 block font-serif text-5xl {{ $warna }}">{{ $angka }}</strong><p class="mt-3 text-xs leading-5 text-stone-500">{{ $catatan }}</p></article>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="mt-4 rounded-3xl border border-dashed border-stone-400 px-6 py-16 text-center text-stone-400">Belum ada PR Plan untuk dievaluasi.</div>
                @endif
            </section>
        @else
            <section class="mt-6">
                <div class="flex flex-wrap items-end justify-between gap-4 rounded-3xl border border-stone-300 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
                    <label class="block w-full max-w-xl text-xs font-bold">Batch magang
                        <select wire:model.live="batchId" class="mt-1.5 w-full rounded-xl border-stone-300 bg-stone-50 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Pilih batch</option>
                            @foreach ($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->nama }} · {{ $batch->mulai->format('d M') }}–{{ $batch->selesai->format('d M Y') }}</option>@endforeach
                        </select>
                    </label>
                    <button wire:click="unduhCsvMagang" @disabled(! $batchId) class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-40">Unduh CSV rekap</button>
                </div>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @forelse ($rekapMagang as $peserta)
                        <article class="rounded-[2rem] border border-stone-300 bg-white p-6 shadow-lg shadow-stone-950/5 dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-start justify-between gap-4"><div><p class="text-[.65rem] font-semibold uppercase tracking-[.16em] text-indigo-600 dark:text-indigo-400">Peserta magang</p><h2 class="mt-1 text-2xl font-semibold">{{ $peserta['nama'] }}</h2></div><strong class="rounded-2xl bg-indigo-600 px-4 py-2 text-2xl text-white">{{ $peserta['jumlah_penugasan'] }}</strong></div>
                            <div class="mt-5 grid grid-cols-4 gap-2 text-center"><div class="rounded-xl bg-stone-100 p-2 dark:bg-zinc-950"><strong>{{ $peserta['ragam_kegiatan'] }}</strong><span class="block text-[.58rem] uppercase text-stone-400">Ragam</span></div><div class="rounded-xl bg-stone-100 p-2 dark:bg-zinc-950"><strong>{{ $peserta['jumlah_pembimbing'] }}</strong><span class="block text-[.58rem] uppercase text-stone-400">Pembimbing</span></div><div class="rounded-xl bg-stone-100 p-2 dark:bg-zinc-950"><strong>{{ $peserta['karya_latihan'] }}</strong><span class="block text-[.58rem] uppercase text-stone-400">Karya</span></div><div class="rounded-xl bg-stone-100 p-2 dark:bg-zinc-950"><strong>{{ $peserta['catatan_pembimbing'] }}</strong><span class="block text-[.58rem] uppercase text-stone-400">Catatan</span></div></div>
                            <div class="mt-5"><p class="text-[.65rem] font-black uppercase tracking-[.14em] text-stone-400">Sebaran peran</p><div class="mt-2 flex flex-wrap gap-2">@forelse ($peserta['peran'] as $peran => $jumlah)<span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-bold text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-300">{{ $peran }} · {{ $jumlah }}</span>@empty<span class="text-xs text-stone-400">Belum ada penugasan.</span>@endforelse</div></div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-3xl border border-dashed border-stone-400 px-6 py-16 text-center text-stone-400">Pilih batch yang memiliki peserta untuk melihat rekap.</div>
                    @endforelse
                </div>
            </section>
        @endif
    </div>
</div>
