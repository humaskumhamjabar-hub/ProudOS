<div data-proud-page>
    <main class="space-y-6">
        <header class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p>Ruang kerja hari ini</p>
                <h1>Hari ini</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6">Penugasan, tenggat, dan hal yang perlu segera ditindaklanjuti.</p>
            </div>
            <time datetime="{{ now()->toDateString() }}" class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                {{ now()->translatedFormat('l, j F Y') }}
            </time>
        </header>

        @if ($butuhTindakan->isNotEmpty())
            <section aria-labelledby="butuh-tindakan-heading" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/50 sm:p-5">
                <div class="flex items-start gap-3">
                    <div aria-hidden="true" class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-200">!</div>
                    <div>
                        <h2 id="butuh-tindakan-heading" class="text-lg font-semibold text-amber-950 dark:text-amber-100">Perlu tindakan</h2>
                        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">Penugasan berikut belum dikonfirmasi atau membutuhkan pengganti.</p>
                    </div>
                </div>

                <ul class="mt-4 divide-y divide-amber-200 dark:divide-amber-900">
                    @foreach ($butuhTindakan as $item)
                        <li class="flex flex-col gap-2 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-zinc-950 dark:text-zinc-100">{{ $item['orang']['nama'] ?? 'Akun tidak tersedia' }}</span>
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item['penugasan']->peran->nama ?? 'Penugasan' }}</span>
                                    @if ($item['penugasan']->status === 'butuh_pengganti')
                                        <span class="rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">Butuh pengganti</span>
                                    @else
                                        <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
                                            {{ $item['penugasan']->dibaca_at ? 'Dibaca, belum diterima' : 'Belum dibaca' }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <time class="shrink-0 text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                {{ $item['penugasan']->mulai_at?->format('d/m H:i') ?? $item['penugasan']->deadline_at?->format('d/m H:i') }}
                            </time>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <div class="grid gap-6 xl:grid-cols-2">
            <section aria-labelledby="penugasan-berjam-heading" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-4 dark:border-zinc-800 sm:px-5">
                    <h2 id="penugasan-berjam-heading" class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Penugasan berjam</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Liputan dan kegiatan dengan waktu kerja hari ini.</p>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($berjam as $p)
                        <article wire:key="berjam-{{ $p->id }}" class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-950 dark:text-zinc-100">
                                    {{ $p->mulai_at->format('H:i') }}–{{ $p->selesai_at?->format('H:i') }}
                                    <span class="text-zinc-500 dark:text-zinc-400">· {{ $p->peran->nama ?? 'Penugasan' }}</span>
                                </div>
                                @if ($p->catatan)
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $p->catatan }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                @if ($p->diterima_at)
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">Diterima</span>
                                @else
                                    <button wire:click="terima({{ $p->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">Terima</button>
                                @endif
                                @if ($p->untuk_type === 'tugas')
                                    <a href="{{ route('tugas.kerjakan', $p->untuk_id) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-950 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">Kerjakan</a>
                                @elseif ($p->untuk_type === 'paket_konten')
                                    <a href="{{ route('produksi.index', ['paket' => $p->untuk_id]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-950 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">Kerjakan</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tidak ada kegiatan berjam hari ini.</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Jadwal baru akan muncul otomatis di sini.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <section aria-labelledby="pekerjaan-deadline-heading" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="border-b border-zinc-200 px-4 py-4 dark:border-zinc-800 sm:px-5">
                    <h2 id="pekerjaan-deadline-heading" class="text-lg font-semibold text-zinc-950 dark:text-zinc-100">Pekerjaan berdeadline</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Pekerjaan aktif yang perlu selesai sebelum tenggat.</p>
                </div>

                <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($berdeadline as $p)
                        <article wire:key="deadline-{{ $p->id }}" class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-950 dark:text-zinc-100">{{ $p->peran->nama ?? 'Pekerjaan' }}</div>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tenggat {{ $p->deadline_at?->translatedFormat('j M H:i') ?? 'belum ditentukan' }}</p>
                                @if ($p->catatan)
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $p->catatan }}</p>
                                @endif
                            </div>
                            <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                                @if ($p->diterima_at)
                                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">Diterima</span>
                                @else
                                    <button wire:click="terima({{ $p->id }})" class="inline-flex min-h-10 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">Terima</button>
                                @endif
                                @if ($p->untuk_type === 'tugas')
                                    <a href="{{ route('tugas.kerjakan', $p->untuk_id) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-950 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">Kerjakan</a>
                                @elseif ($p->untuk_type === 'paket_konten')
                                    <a href="{{ route('produksi.index', ['paket' => $p->untuk_id]) }}" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-semibold text-zinc-950 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:hover:bg-zinc-800">Kerjakan</a>
                                @endif
                            </div>
                        </article>
                    @empty
                        <div class="px-5 py-10 text-center">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Tidak ada pekerjaan berdeadline aktif.</p>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Pekerjaan baru akan muncul otomatis di sini.</p>
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </main>
</div>
