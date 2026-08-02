<div data-proud-page>
  <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs">Jadwal terpadu</p>
            <h1>Kalender</h1>
            <p class="mt-2 text-sm">{{ $awal->translatedFormat('F Y') }}, seluruh agenda tim dalam satu tampilan.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('kelola_agenda')<button wire:click="unduhJadwalHarian" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-semibold text-white dark:bg-white dark:text-zinc-900">Cetak hari ini</button>@endcan
            <button wire:click="gantiBulan(-1)" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">‹ Sebelumnya</button>
            <button wire:click="gantiBulan(1)" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">Berikutnya ›</button>
        </div>
    </header>

    <div class="grid grid-cols-7 gap-px overflow-hidden rounded-xl border border-zinc-200 bg-zinc-200 dark:border-zinc-700 dark:bg-zinc-700">
        @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $hari)
            <div class="bg-zinc-50 p-2 text-center text-xs font-semibold uppercase text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ $hari }}</div>
        @endforeach

        @php
            $mulaiGrid = $awal->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
            $akhirGrid = $akhir->copy()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
        @endphp

        @for ($tgl = $mulaiGrid->copy(); $tgl->lte($akhirGrid); $tgl->addDay())
            @php $kunci = $tgl->format('Y-m-d'); @endphp
            <div class="min-h-24 bg-white p-1.5 dark:bg-zinc-900 {{ $tgl->month !== $awal->month ? 'opacity-40' : '' }}">
                <div class="text-xs font-medium {{ $tgl->isToday() ? 'inline-flex h-5 w-5 items-center justify-center rounded-full bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'text-zinc-500' }}">
                    {{ $tgl->day }}
                </div>
                @foreach ($agendaPerTanggal->get($kunci, collect()) as $agenda)
                    <div wire:key="a-{{ $agenda->id }}" class="mt-1 truncate rounded bg-indigo-50 px-1.5 py-0.5 text-xs text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300" title="{{ $agenda->judul }}">
                        {{ $agenda->mulai_at->format('H:i') }} {{ $agenda->judul }}
                    </div>
                @endforeach
            </div>
        @endfor
    </div>
  </main>
</div>
