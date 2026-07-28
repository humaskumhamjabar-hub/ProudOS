<div class="mx-auto max-w-5xl space-y-8 p-6">
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Hari ini</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ now()->translatedFormat('l, j F Y') }}</p>
    </div>

    @if ($butuhTindakan->isNotEmpty())
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950">
            <h2 class="mb-3 font-semibold text-amber-900 dark:text-amber-200">
                Butuh tindakan — belum dikonfirmasi / butuh pengganti
            </h2>
            <ul class="space-y-2">
                @foreach ($butuhTindakan as $item)
                    <li class="flex items-center justify-between rounded-lg bg-white p-3 text-sm shadow-sm dark:bg-zinc-900">
                        <div>
                            <span class="font-medium">{{ $item['orang']['nama'] ?? '—' }}</span>
                            <span class="text-zinc-500">· {{ $item['penugasan']->peran->nama ?? '' }}</span>
                            @if ($item['penugasan']->status === 'butuh_pengganti')
                                <span class="ml-2 rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-200">butuh pengganti</span>
                            @else
                                <span class="ml-2 rounded bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900 dark:text-amber-200">
                                    {{ $item['penugasan']->dibaca_at ? 'dibaca, belum diterima' : 'belum dibaca' }}
                                </span>
                            @endif
                        </div>
                        <div class="text-xs text-zinc-500">
                            {{ $item['penugasan']->mulai_at?->format('d/m H:i') ?? $item['penugasan']->deadline_at?->format('d/m H:i') }}
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section>
        <h2 class="mb-3 font-semibold text-zinc-900 dark:text-white">Penugasan berjam hari ini</h2>
        @forelse ($berjam as $p)
            <div wire:key="berjam-{{ $p->id }}" class="mb-2 flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <div class="font-medium text-zinc-900 dark:text-white">
                        {{ $p->mulai_at->format('H:i') }}–{{ $p->selesai_at?->format('H:i') }}
                        <span class="text-zinc-500">· {{ $p->peran->nama ?? '' }}</span>
                    </div>
                    @if ($p->catatan)
                        <p class="text-sm text-zinc-500">{{ $p->catatan }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($p->diterima_at)
                        <span class="rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-200">diterima</span>
                    @else
                        <button wire:click="terima({{ $p->id }})" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                            Terima
                        </button>
                    @endif
                    @if ($p->untuk_type === 'tugas')
                        <a href="{{ route('tugas.kerjakan', $p->untuk_id) }}" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">
                            Kerjakan
                        </a>
                    @elseif ($p->untuk_type === 'paket_konten')
                        <a href="{{ route('produksi.index', ['paket' => $p->untuk_id]) }}" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">Kerjakan</a>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">Tidak ada liputan atau kegiatan berjam hari ini.</p>
        @endforelse
    </section>

    <section>
        <h2 class="mb-3 font-semibold text-zinc-900 dark:text-white">Pekerjaan berdeadline</h2>
        @forelse ($berdeadline as $p)
            <div wire:key="deadline-{{ $p->id }}" class="mb-2 flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <div class="font-medium text-zinc-900 dark:text-white">
                        {{ $p->peran->nama ?? 'Pekerjaan' }}
                        <span class="text-sm text-zinc-500">· tenggat {{ $p->deadline_at?->translatedFormat('j M H:i') ?? '—' }}</span>
                    </div>
                    @if ($p->catatan)
                        <p class="text-sm text-zinc-500">{{ $p->catatan }}</p>
                    @endif
                </div>
                <div class="flex items-center gap-2">
                    @if ($p->diterima_at)
                        <span class="rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900 dark:text-green-200">diterima</span>
                    @else
                        <button wire:click="terima({{ $p->id }})" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                            Terima
                        </button>
                    @endif
                    @if ($p->untuk_type === 'tugas')
                        <a href="{{ route('tugas.kerjakan', $p->untuk_id) }}" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">
                            Kerjakan
                        </a>
                    @elseif ($p->untuk_type === 'paket_konten')
                        <a href="{{ route('produksi.index', ['paket' => $p->untuk_id]) }}" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">Kerjakan</a>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-sm text-zinc-500">Tidak ada pekerjaan berdeadline yang aktif.</p>
        @endforelse
    </section>
</div>
