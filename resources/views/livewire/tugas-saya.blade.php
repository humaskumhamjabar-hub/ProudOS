<div class="mx-auto max-w-4xl space-y-6 p-6">
    <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Tugas Saya</h1>

    @forelse ($penugasan as $p)
        <div wire:key="p-{{ $p->id }}" class="flex items-center justify-between rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
            <div>
                <div class="font-medium text-zinc-900 dark:text-white">
                    @if ($p->untuk_type === 'tugas' && $tugas->has($p->untuk_id))
                        {{ $tugas[$p->untuk_id]->judul }}
                    @elseif ($p->untuk_type === 'paket_konten' && $paket->has($p->untuk_id))
                        {{ $paket[$p->untuk_id]->judul }}
                    @else
                        {{ $p->peran->nama ?? 'Penugasan' }}
                    @endif
                </div>
                <div class="text-sm text-zinc-500">
                    {{ $p->peran->nama ?? '' }}
                    @if ($p->tipe === 'berjam')
                        · {{ $p->mulai_at?->translatedFormat('j M H:i') }}–{{ $p->selesai_at?->format('H:i') }}
                    @else
                        · tenggat {{ $p->deadline_at?->translatedFormat('j M H:i') ?? '—' }}
                    @endif
                    @if ($p->pembimbing_id)
                        · <span class="text-indigo-600 dark:text-indigo-400">didampingi pembimbing</span>
                    @endif
                </div>
            </div>
            @if ($p->untuk_type === 'tugas')
                <a href="{{ route('tugas.kerjakan', $p->untuk_id) }}" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                    Kerjakan tugas ini
                </a>
            @elseif ($p->untuk_type === 'paket_konten')
                <a href="{{ route('produksi.index', ['paket' => $p->untuk_id]) }}" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-900">
                    Buka meja produksi
                </a>
            @endif
        </div>
    @empty
        <p class="text-sm text-zinc-500">Tidak ada penugasan aktif. 🎉</p>
    @endforelse
</div>
