<div class="mx-auto max-w-3xl space-y-6 p-6">
    <div class="flex items-start justify-between">
        <div>
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
                <button wire:click="tandaiSelesai" class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-500">Tandai selesai</button>
            @endif
        </div>
    </div>

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
        <h2 class="mb-3 font-semibold text-zinc-900 dark:text-white">Bahan</h2>
        <ul class="mb-4 space-y-1 text-sm">
            @forelse ($this->tugas->bahan as $b)
                <li wire:key="b-{{ $b->id }}" class="flex items-center gap-2 text-zinc-700 dark:text-zinc-300">
                    <span>📎</span> {{ $b->nama_asli }}
                </li>
            @empty
                <li class="text-zinc-500">Belum ada bahan.</li>
            @endforelse
        </ul>
        <form wire:submit="unggahBahan" class="flex items-center gap-3">
            <input type="file" wire:model="unggahan" multiple class="text-sm" />
            <button type="submit" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:hover:bg-zinc-800">
                Unggah
            </button>
            <span wire:loading wire:target="unggahan" class="text-xs text-zinc-500">mengunggah…</span>
        </form>
        @error('unggahan.*') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </section>

    <section class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="mb-3 font-semibold text-zinc-900 dark:text-white">Komentar</h2>
        <ul class="mb-4 space-y-3">
            @forelse ($this->tugas->komentar as $k)
                <li wire:key="k-{{ $k->id }}" class="text-sm">
                    <span class="text-zinc-500">{{ $k->created_at->format('d/m H:i') }}</span>
                    <p class="text-zinc-800 dark:text-zinc-200">{{ $k->isi }}</p>
                </li>
            @empty
                <li class="text-sm text-zinc-500">Belum ada komentar.</li>
            @endforelse
        </ul>
        <form wire:submit="kirimKomentar" class="flex gap-2">
            <input type="text" wire:model="komentarBaru" placeholder="Tulis komentar…"
                   class="flex-1 rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
            <button type="submit" class="rounded-lg bg-zinc-900 px-3 py-1.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">Kirim</button>
        </form>
        @error('komentarBaru') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
    </section>

    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40">
        <h2 class="font-semibold text-amber-950 dark:text-amber-100">Catatan pembimbing</h2>
        <p class="mt-1 text-sm text-amber-800 dark:text-amber-200">Masukan pendampingan tersimpan untuk rekap pembelajaran magang.</p>
        <ul class="mt-4 space-y-3">
            @forelse ($catatanPembimbing as $catatan)
                <li wire:key="catatan-pembimbing-{{ $catatan->id }}" class="rounded-xl bg-white p-3 text-sm dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3 text-xs text-stone-500"><strong>{{ $namaPembimbing[$catatan->oleh_id] ?? 'Pembimbing' }}</strong><span>{{ $catatan->created_at->format('d/m H:i') }}</span></div>
                    <p class="mt-2 whitespace-pre-line text-stone-800 dark:text-zinc-200">{{ $catatan->isi }}</p>
                </li>
            @empty
                <li class="text-sm text-amber-800 dark:text-amber-200">Belum ada catatan pembimbing.</li>
            @endforelse
        </ul>
        @if ($bolehMemberiCatatan)
            <form wire:submit="simpanCatatanPembimbing" class="mt-4 space-y-2">
                <label for="catatan-pembimbing" class="text-sm font-semibold text-amber-950 dark:text-amber-100">Tambahkan catatan</label>
                <textarea id="catatan-pembimbing" wire:model="catatanPembimbingBaru" rows="3" class="w-full rounded-xl border-amber-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-amber-900 dark:bg-zinc-950 dark:text-white"></textarea>
                @error('catatanPembimbingBaru') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-xl bg-amber-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-amber-700">Simpan catatan</button>
            </form>
        @endif
    </section>
</div>
