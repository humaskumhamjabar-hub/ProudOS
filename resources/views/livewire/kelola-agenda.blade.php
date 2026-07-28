<div class="mx-auto max-w-6xl space-y-6 p-4 sm:p-6">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="mb-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-600 dark:text-indigo-400">Ruang kendali</p>
            <h1 class="text-2xl font-semibold text-zinc-950 dark:text-white">Kelola Agenda</h1>
            <p class="mt-1 max-w-2xl text-sm text-zinc-500 dark:text-zinc-400">Satu sumber jadwal kegiatan, lengkap sampai jam dan kebutuhan tim Humas.</p>
        </div>
        <button wire:click="buat" class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-900">
            + Agenda baru
        </button>
    </header>

    @if (session('agenda-tersimpan'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('agenda-tersimpan') }}
        </div>
    @endif

    @if ($formTerbuka)
        <section class="overflow-hidden rounded-2xl border border-indigo-200 bg-white shadow-sm dark:border-indigo-900 dark:bg-zinc-900">
            <div class="border-b border-zinc-200 bg-indigo-50/70 px-5 py-4 dark:border-zinc-800 dark:bg-indigo-950/30">
                <h2 class="font-semibold text-zinc-950 dark:text-white">{{ $agendaId ? 'Ubah agenda' : 'Agenda baru' }}</h2>
                <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">Jam mulai wajib diisi karena menjadi acuan pemeriksaan bentrok penugasan.</p>
            </div>

            <form wire:submit="simpan" class="grid gap-5 p-5 lg:grid-cols-2">
                <label class="lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Judul kegiatan</span>
                    <input wire:model="judul" type="text" autofocus class="w-full rounded-lg border-zinc-300 bg-white text-zinc-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" placeholder="Contoh: Rapat koordinasi pelayanan publik">
                    @error('judul') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Mulai</span>
                    <input wire:model="mulaiAt" type="datetime-local" class="w-full rounded-lg border-zinc-300 bg-white text-zinc-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    @error('mulaiAt') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Selesai <span class="font-normal text-zinc-400">(opsional)</span></span>
                    <input wire:model="selesaiAt" type="datetime-local" class="w-full rounded-lg border-zinc-300 bg-white text-zinc-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                    @error('selesaiAt') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Lokasi</span>
                    <input wire:model="lokasi" type="text" class="w-full rounded-lg border-zinc-300 bg-white text-zinc-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" placeholder="Ruang rapat / alamat kegiatan">
                    @error('lokasi') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label>
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Status</span>
                    <select wire:model="status" class="w-full rounded-lg border-zinc-300 bg-white text-zinc-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        <option value="rencana">Rencana</option>
                        <option value="berjalan">Berjalan</option>
                        <option value="selesai">Selesai</option>
                        <option value="batal">Batal</option>
                    </select>
                </label>

                <fieldset class="lg:col-span-2">
                    <legend class="mb-2 text-sm font-medium text-zinc-800 dark:text-zinc-200">Kebutuhan Humas</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach (['foto' => 'Foto', 'video' => 'Video', 'berita' => 'Berita', 'caption' => 'Caption'] as $nilai => $label)
                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 transition has-[:checked]:border-indigo-500 has-[:checked]:bg-indigo-50 has-[:checked]:text-indigo-700 dark:border-zinc-700 dark:text-zinc-300 dark:has-[:checked]:border-indigo-500 dark:has-[:checked]:bg-indigo-950 dark:has-[:checked]:text-indigo-300">
                                <input wire:model="kebutuhanHumas" type="checkbox" value="{{ $nilai }}" class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('kebutuhanHumas.*') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </fieldset>

                <label class="lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-zinc-800 dark:text-zinc-200">Deskripsi / catatan kegiatan</span>
                    <textarea wire:model="deskripsi" rows="3" class="w-full rounded-lg border-zinc-300 bg-white text-zinc-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" placeholder="Konteks yang perlu diketahui tim peliputan"></textarea>
                    @error('deskripsi') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <div class="flex justify-end gap-2 lg:col-span-2">
                    <button wire:click="tutupForm" type="button" class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Batal</button>
                    <button type="submit" class="rounded-lg bg-zinc-950 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-800 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">Simpan agenda</button>
                </div>
            </form>
        </section>
    @endif

    <section>
        <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-zinc-950 dark:text-white">Daftar agenda</h2>
            <label class="flex items-center gap-2 text-sm text-zinc-500">
                Tampilkan
                <select wire:model.live="filterStatus" class="rounded-lg border-zinc-300 bg-white py-1.5 text-sm text-zinc-800 focus:border-indigo-500 focus:ring-indigo-500 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                    <option value="aktif">Aktif</option>
                    <option value="rencana">Rencana</option>
                    <option value="berjalan">Berjalan</option>
                    <option value="selesai">Selesai</option>
                    <option value="batal">Batal</option>
                </select>
            </label>
        </div>

        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            @forelse ($agenda as $item)
                <article wire:key="agenda-{{ $item->id }}" class="group grid gap-3 border-b border-zinc-200 p-4 last:border-b-0 sm:grid-cols-[8rem_1fr_auto] sm:items-center dark:border-zinc-800">
                    <div>
                        <div class="text-sm font-semibold text-zinc-950 dark:text-white">{{ $item->mulai_at->translatedFormat('j M Y') }}</div>
                        <div class="text-sm text-indigo-600 dark:text-indigo-400">{{ $item->mulai_at->format('H:i') }}{{ $item->selesai_at ? '–'.$item->selesai_at->format('H:i') : '' }}</div>
                    </div>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate font-medium text-zinc-950 dark:text-white">{{ $item->judul }}</h3>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $item->status }}</span>
                        </div>
                        <p class="mt-1 truncate text-sm text-zinc-500">
                            {{ $item->lokasi ?: 'Lokasi belum diisi' }}
                            @if ($item->kebutuhan_humas)
                                · {{ collect($item->kebutuhan_humas)->map(fn ($nilai) => ucfirst($nilai))->join(', ') }}
                            @endif
                        </p>
                    </div>
                    <button wire:click="edit({{ $item->id }})" class="justify-self-start rounded-lg border border-zinc-300 px-3 py-1.5 text-sm font-medium text-zinc-700 opacity-100 hover:bg-zinc-50 sm:justify-self-end sm:opacity-0 sm:group-hover:opacity-100 sm:focus:opacity-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                        Ubah
                    </button>
                </article>
            @empty
                <div class="px-6 py-12 text-center">
                    <div class="text-3xl">◷</div>
                    <p class="mt-2 font-medium text-zinc-800 dark:text-zinc-200">Belum ada agenda pada status ini.</p>
                    <p class="mt-1 text-sm text-zinc-500">Buat agenda pertama untuk mulai menyusun jadwal tim.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>
