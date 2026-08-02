<div data-proud-page>
    <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8">
        <header class="flex items-end justify-between gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[.16em] text-indigo-600 dark:text-indigo-400">Alur kerja tim</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight">Kelola Tugas</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600 dark:text-zinc-400">Buat brief, tentukan tenggat, lalu pilih pelaksana. Tugas aktif langsung muncul di ponsel orang yang ditunjuk.</p>
            </div>
            <button type="button" wire:click="buat" class="shrink-0 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                Buat tugas
            </button>
        </header>

        @if (session('sukses'))
            <div role="status" class="mt-5 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200">{{ session('sukses') }}</div>
        @endif

        @if ($formTerbuka)
            <section class="mt-5 rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6" aria-labelledby="form-tugas-title">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="form-tugas-title" class="text-lg font-bold">{{ $tugasId ? 'Ubah tugas' : 'Tugas baru' }}</h2>
                    <button type="button" wire:click="tutupForm" class="rounded-lg px-3 py-2 text-sm font-medium text-stone-600 hover:bg-stone-100 dark:text-zinc-300 dark:hover:bg-zinc-800">Tutup</button>
                </div>
                <form wire:submit="simpan" class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="sm:col-span-2 text-sm font-semibold">Judul
                        <input wire:model="judul" type="text" required autofocus class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('judul') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="sm:col-span-2 text-sm font-semibold">Brief
                        <textarea wire:model="brief" rows="4" class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white" placeholder="Hasil yang diharapkan, bahan acuan, dan catatan penting"></textarea>
                        @error('brief') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-semibold">Tenggat
                        <input wire:model="deadlineAt" type="datetime-local" class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('deadlineAt') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-semibold">Kegiatan terkait
                        <select wire:model="agendaId" class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="">Tanpa agenda</option>
                            @foreach ($agenda as $item)<option value="{{ $item->id }}">{{ $item->judul }}</option>@endforeach
                        </select>
                        @error('agendaId') <span class="mt-1 block text-xs text-red-600">{{ $message }}</span> @enderror
                    </label>
                    @if ($tugasId)
                        <label class="text-sm font-semibold">Status
                            <select wire:model="status" class="mt-1.5 w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                <option value="baru">Baru</option><option value="dikerjakan">Dikerjakan</option><option value="selesai">Selesai</option>
                            </select>
                        </label>
                    @endif
                    <div class="sm:col-span-2 flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="tutupForm" class="rounded-xl px-4 py-2.5 text-sm font-bold text-stone-600 hover:bg-stone-100 dark:text-zinc-300 dark:hover:bg-zinc-800">Batal</button>
                        <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50" wire:loading.attr="disabled" wire:target="simpan">Simpan tugas</button>
                    </div>
                </form>
            </section>
        @endif

        @if ($tugasTimId)
            @php($tugasAktif = $tugas->firstWhere('id', $tugasTimId))
            <section class="mt-5 rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900 sm:p-6" aria-labelledby="pelaksana-title">
                <div class="flex items-start justify-between gap-3">
                    <div><p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Atur pelaksana</p><h2 id="pelaksana-title" class="mt-1 text-lg font-bold">{{ $tugasAktif?->judul }}</h2></div>
                    <button type="button" wire:click="tutupPelaksana" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">Tutup</button>
                </div>
                <form wire:submit="simpanPenugasan" class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="text-sm font-semibold">Pelaksana
                        <select wire:model="anggotaId" required class="mt-1.5 w-full rounded-xl border-sky-200 bg-white text-stone-950 focus:border-sky-600 focus:ring-sky-600 dark:border-sky-900 dark:bg-zinc-950 dark:text-white"><option value="">Pilih orang</option>@foreach ($anggota as $orang)<option value="{{ $orang->id }}">{{ $orang->nama }}</option>@endforeach</select>
                        @error('anggotaId') <span class="mt-1 block text-xs text-red-700 dark:text-red-300">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-semibold">Peran
                        <select wire:model="peranId" required class="mt-1.5 w-full rounded-xl border-sky-200 bg-white text-stone-950 focus:border-sky-600 focus:ring-sky-600 dark:border-sky-900 dark:bg-zinc-950 dark:text-white"><option value="">Pilih peran</option>@foreach ($peran as $item)<option value="{{ $item->id }}">{{ $item->nama }}</option>@endforeach</select>
                        @error('peranId') <span class="mt-1 block text-xs text-red-700 dark:text-red-300">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-semibold">Tenggat pelaksana
                        <input wire:model="deadlinePenugasanAt" type="datetime-local" required class="mt-1.5 w-full rounded-xl border-sky-200 bg-white text-stone-950 focus:border-sky-600 focus:ring-sky-600 dark:border-sky-900 dark:bg-zinc-950 dark:text-white">
                        @error('deadlinePenugasanAt') <span class="mt-1 block text-xs text-red-700 dark:text-red-300">{{ $message }}</span> @enderror
                    </label>
                    <label class="text-sm font-semibold">Pembimbing (wajib untuk magang)
                        <select wire:model="pembimbingId" class="mt-1.5 w-full rounded-xl border-sky-200 bg-white text-stone-950 focus:border-sky-600 focus:ring-sky-600 dark:border-sky-900 dark:bg-zinc-950 dark:text-white"><option value="">Tanpa pembimbing</option>@foreach ($anggota as $orang)<option value="{{ $orang->id }}">{{ $orang->nama }}</option>@endforeach</select>
                        @error('pembimbingId') <span class="mt-1 block text-xs text-red-700 dark:text-red-300">{{ $message }}</span> @enderror
                    </label>
                    <label class="sm:col-span-2 text-sm font-semibold">Catatan untuk pelaksana
                        <textarea wire:model="catatanPenugasan" rows="2" class="mt-1.5 w-full rounded-xl border-sky-200 bg-white text-stone-950 focus:border-sky-600 focus:ring-sky-600 dark:border-sky-900 dark:bg-zinc-950 dark:text-white"></textarea>
                    </label>
                    <div class="sm:col-span-2 flex justify-end"><button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Tambahkan pelaksana</button></div>
                </form>
                <div class="mt-5 border-t border-zinc-200 pt-4 dark:border-zinc-800">
                    <h3 class="text-sm font-bold">Pelaksana saat ini</h3>
                    <ul class="mt-2 space-y-2">
                        @forelse ($penugasan->get($tugasTimId, collect()) as $item)
                            <li wire:key="penugasan-{{ $item->id }}" class="flex items-center justify-between gap-3 rounded-xl bg-white px-3 py-3 text-sm dark:bg-zinc-900">
                                <div><strong>{{ $namaOrang[$item->user_id] ?? 'Pengguna tidak tersedia' }}</strong><span class="block text-xs text-stone-500">{{ $item->peran?->nama }} · {{ $item->deadline_at?->translatedFormat('j M Y H:i') }}@if($item->pembimbing_id) · pembimbing: {{ $namaOrang[$item->pembimbing_id] ?? '—' }}@endif</span></div>
                                @if ($item->status === 'aktif')<button type="button" wire:click="batalkanPenugasan({{ $item->id }})" wire:confirm="Batalkan penugasan ini?" class="rounded-lg px-3 py-2 text-xs font-bold text-red-700 hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-950">Batalkan</button>@else<span class="text-xs font-bold uppercase text-stone-400">{{ str_replace('_', ' ', $item->status) }}</span>@endif
                            </li>
                        @empty
                            <li class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada pelaksana.</li>
                        @endforelse
                    </ul>
                </div>
            </section>
        @endif

        <div class="mt-6 flex gap-2 overflow-x-auto pb-1" aria-label="Filter tugas">
            @foreach (['aktif' => 'Aktif', 'baru' => 'Baru', 'dikerjakan' => 'Dikerjakan', 'selesai' => 'Selesai', 'semua' => 'Semua'] as $nilai => $label)
                <button type="button" wire:click="$set('filterStatus', '{{ $nilai }}')" class="min-w-fit rounded-full px-4 py-2 text-sm font-bold {{ $filterStatus === $nilai ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'border border-zinc-300 bg-white text-zinc-600 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300' }}">{{ $label }}</button>
            @endforeach
        </div>

        <section class="mt-4 space-y-3" aria-label="Daftar tugas">
            @forelse ($tugas as $item)
                <article wire:key="tugas-{{ $item->id }}" class="rounded-2xl border border-stone-300 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-5">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $item->status === 'selesai' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : ($item->status === 'dikerjakan' ? 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300') }}">{{ ucfirst($item->status) }}</span>@if($item->deadline_at)<span class="text-xs font-medium text-stone-500">Tenggat {{ $item->deadline_at->translatedFormat('j M, H:i') }}</span>@endif</div>
                            <h2 class="mt-2 text-lg font-bold leading-snug">{{ $item->judul }}</h2>
                            @if($item->brief)<p class="mt-1 line-clamp-2 max-w-2xl text-sm leading-6 text-stone-600 dark:text-zinc-400">{{ $item->brief }}</p>@endif
                            <div class="mt-3 flex flex-wrap gap-2">
                                @forelse ($penugasan->get($item->id, collect()) as $orang)
                                    <span class="rounded-full border border-stone-200 bg-stone-50 px-2.5 py-1 text-xs font-medium dark:border-zinc-700 dark:bg-zinc-950">{{ $namaOrang[$orang->user_id] ?? 'Pengguna' }} · {{ $orang->peran?->nama }}</span>
                                @empty
                                    <span class="text-xs font-bold text-amber-700 dark:text-amber-400">Belum ada pelaksana</span>
                                @endforelse
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:shrink-0">
                            <button type="button" wire:click="edit({{ $item->id }})" class="rounded-xl border border-stone-300 px-3 py-2 text-sm font-bold hover:bg-stone-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Ubah</button>
                            <button type="button" wire:click="aturPelaksana({{ $item->id }})" class="rounded-xl bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Pelaksana</button>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-stone-400 px-5 py-14 text-center"><h2 class="font-bold">Belum ada tugas pada filter ini</h2><p class="mt-1 text-sm text-stone-500">Buat tugas pertama lalu tentukan pelaksananya.</p></div>
            @endforelse
        </section>
    </div>
</div>
