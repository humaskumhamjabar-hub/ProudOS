<div class="min-h-full bg-stone-50 text-stone-950 dark:bg-zinc-950 dark:text-white">
    <div class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
        <header class="relative overflow-hidden rounded-[1.75rem] border border-stone-200 bg-[#f6efe2] px-5 py-6 shadow-sm sm:px-7 sm:py-8 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="pointer-events-none absolute -right-12 -top-20 h-52 w-52 rounded-full border-[28px] border-orange-300/30 dark:border-orange-500/10"></div>
            <div class="pointer-events-none absolute bottom-0 right-28 h-px w-40 rotate-[-18deg] bg-orange-500/40"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <div class="mb-3 flex items-center gap-3 text-xs font-bold uppercase tracking-[0.2em] text-orange-700 dark:text-orange-400">
                        <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                        Meja editorial
                    </div>
                    <h1 class="max-w-2xl text-3xl font-semibold tracking-[-0.035em] text-stone-950 sm:text-4xl dark:text-white">PR Plan</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-600 sm:text-base dark:text-zinc-400">
                        Susun antrean komunikasi sebelum masuk produksi. Waktu kasar tinggal di sini; tanggal resmi hanya lahir saat item dijadwalkan ke Agenda.
                    </p>
                </div>
                <button wire:click="buatPlan" class="inline-flex min-h-11 items-center justify-center gap-2 self-start rounded-full bg-stone-950 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-stone-900/10 transition hover:-translate-y-0.5 hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 lg:self-auto dark:bg-orange-500 dark:text-zinc-950 dark:hover:bg-orange-400 dark:focus:ring-offset-zinc-900">
                    <span class="text-lg leading-none">+</span>
                    PR Plan baru
                </button>
            </div>
        </header>

        @if (session('pr-plan-tersimpan'))
            <div role="status" class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/50 dark:text-emerald-200">
                <span aria-hidden="true" class="mt-0.5">✓</span>
                {{ session('pr-plan-tersimpan') }}
            </div>
        @endif

        @if ($formPlanTerbuka)
            <section class="overflow-hidden rounded-3xl border border-orange-200 bg-white shadow-xl shadow-orange-950/5 dark:border-orange-900/60 dark:bg-zinc-900">
                <div class="flex items-start justify-between border-b border-stone-200 bg-orange-50/70 px-5 py-4 dark:border-zinc-800 dark:bg-orange-950/20">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-700 dark:text-orange-400">Periode kerja</p>
                        <h2 class="mt-1 text-lg font-semibold">{{ $planId ? 'Ubah PR Plan' : 'Buat PR Plan' }}</h2>
                    </div>
                    <button wire:click="tutupSemuaForm" type="button" aria-label="Tutup form PR Plan" class="rounded-full p-2 text-stone-500 transition hover:bg-stone-200 hover:text-stone-950 dark:hover:bg-zinc-800 dark:hover:text-white">✕</button>
                </div>
                <form wire:submit="simpanPlan" class="grid gap-5 p-5 md:grid-cols-2 lg:p-6">
                    <label class="md:col-span-2">
                        <span class="mb-1.5 block text-sm font-semibold">Nama PR Plan</span>
                        <input wire:model="nama" type="text" autofocus placeholder="Contoh: PR Plan Agustus 2026" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('nama') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label class="md:col-span-2">
                        <span class="mb-1.5 block text-sm font-semibold">Tema besar <span class="font-normal text-stone-400">(opsional)</span></span>
                        <input wire:model="tema" type="text" placeholder="Pesan utama yang menyatukan periode ini" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('tema') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-semibold">Awal periode</span>
                        <input wire:model="periodeMulai" type="date" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('periodeMulai') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-semibold">Akhir periode</span>
                        <input wire:model="periodeSelesai" type="date" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('periodeSelesai') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-semibold">Target konten</span>
                        <input wire:model="targetJumlahKonten" type="number" min="1" max="10000" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                        @error('targetJumlahKonten') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                    </label>
                    <label>
                        <span class="mb-1.5 block text-sm font-semibold">Status</span>
                        <select wire:model="statusPlan" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 shadow-sm focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                            <option value="draf">Draf</option>
                            <option value="berjalan">Berjalan</option>
                            <option value="selesai">Selesai</option>
                        </select>
                    </label>
                    <div class="flex justify-end gap-2 md:col-span-2">
                        <button wire:click="tutupSemuaForm" type="button" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold text-stone-700 hover:bg-stone-100 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">Batal</button>
                        <button type="submit" class="rounded-full bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-500">Simpan PR Plan</button>
                    </div>
                </form>
            </section>
        @endif

        <div class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="self-start rounded-3xl border border-stone-200 bg-white p-3 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 lg:sticky lg:top-6">
                <div class="flex items-center justify-between px-2 pb-3 pt-1">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Portofolio</p>
                        <h2 class="mt-0.5 font-semibold">Semua periode</h2>
                    </div>
                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-bold text-stone-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $plans->count() }}</span>
                </div>
                <div class="space-y-2">
                    @forelse ($plans as $plan)
                        <button wire:click="pilihPlan({{ $plan->id }})" wire:key="plan-{{ $plan->id }}" class="w-full rounded-2xl border p-3 text-left transition {{ $planAktif?->id === $plan->id ? 'border-orange-300 bg-orange-50 shadow-sm dark:border-orange-800 dark:bg-orange-950/30' : 'border-transparent hover:border-stone-200 hover:bg-stone-50 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/70' }}">
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-semibold leading-5">{{ $plan->nama }}</span>
                                <span class="mt-0.5 h-2 w-2 shrink-0 rounded-full {{ $plan->status === 'berjalan' ? 'bg-emerald-500' : ($plan->status === 'selesai' ? 'bg-stone-400' : 'bg-amber-400') }}"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-stone-500 dark:text-zinc-400">
                                <span>{{ $plan->periode_mulai->translatedFormat('M Y') }}</span>
                                <span>{{ $plan->items_count }}/{{ $plan->target_jumlah_konten }} item</span>
                            </div>
                        </button>
                    @empty
                        <div class="rounded-2xl border border-dashed border-stone-300 px-4 py-8 text-center dark:border-zinc-700">
                            <p class="text-sm font-medium">Belum ada PR Plan.</p>
                            <p class="mt-1 text-xs text-stone-500">Mulai dari satu periode komunikasi.</p>
                        </div>
                    @endforelse
                </div>
            </aside>

            <main class="min-w-0 space-y-5">
                @if ($planAktif)
                    @php
                        $jumlahItem = $planAktif->items->count();
                        $dijadwalkan = $planAktif->items->whereNotNull('agenda_id')->count();
                        $kemajuan = min(100, (int) round(($jumlahItem / max(1, $planAktif->target_jumlah_konten)) * 100));
                    @endphp
                    <section class="rounded-3xl border border-stone-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="mb-2 flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs font-bold uppercase tracking-wide text-stone-600 dark:bg-zinc-800 dark:text-zinc-300">{{ $planAktif->status }}</span>
                                    <span class="text-sm text-stone-500 dark:text-zinc-400">{{ $planAktif->periode_mulai->translatedFormat('j M') }}–{{ $planAktif->periode_selesai->translatedFormat('j M Y') }}</span>
                                </div>
                                <h2 class="text-2xl font-semibold tracking-[-0.025em]">{{ $planAktif->nama }}</h2>
                                @if ($planAktif->tema)
                                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-600 dark:text-zinc-400">{{ $planAktif->tema }}</p>
                                @endif
                            </div>
                            <div class="flex shrink-0 gap-2">
                                <button wire:click="editPlan({{ $planAktif->id }})" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Ubah</button>
                                <button wire:click="tambahItem({{ $planAktif->id }})" class="rounded-full bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-500">+ Tambah ide</button>
                            </div>
                        </div>
                        <div class="mt-6 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl bg-stone-100 p-4 dark:bg-zinc-800/80">
                                <div class="text-2xl font-semibold">{{ $jumlahItem }}<span class="text-base font-normal text-stone-400">/{{ $planAktif->target_jumlah_konten }}</span></div>
                                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-stone-500 dark:text-zinc-400">Ide dari target</div>
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-stone-200 dark:bg-zinc-700"><div class="h-full rounded-full bg-orange-500" style="width: {{ $kemajuan }}%"></div></div>
                            </div>
                            <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/30">
                                <div class="text-2xl font-semibold text-emerald-800 dark:text-emerald-300">{{ $dijadwalkan }}</div>
                                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-emerald-700/70 dark:text-emerald-400/70">Sudah beragenda</div>
                            </div>
                            <div class="rounded-2xl bg-amber-50 p-4 dark:bg-amber-950/30">
                                <div class="text-2xl font-semibold text-amber-800 dark:text-amber-300">{{ $planAktif->items->where('status', 'ide')->count() }}</div>
                                <div class="mt-1 text-xs font-bold uppercase tracking-wide text-amber-700/70 dark:text-amber-400/70">Masih antre</div>
                            </div>
                        </div>
                    </section>

                    @if ($formItemTerbuka)
                        <section class="rounded-3xl border border-orange-200 bg-white p-5 shadow-lg shadow-orange-950/5 dark:border-orange-900/60 dark:bg-zinc-900 sm:p-6">
                            <div class="mb-5 flex items-start justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-700 dark:text-orange-400">Antrean editorial</p>
                                    <h2 class="mt-1 text-lg font-semibold">{{ $itemId ? 'Ubah item konten' : 'Tambah ide konten' }}</h2>
                                </div>
                                <button wire:click="tutupSemuaForm" type="button" aria-label="Tutup form item" class="rounded-full p-2 text-stone-500 hover:bg-stone-100 dark:hover:bg-zinc-800">✕</button>
                            </div>
                            <form wire:submit="simpanItem" class="grid gap-5 md:grid-cols-2">
                                <label class="md:col-span-2">
                                    <span class="mb-1.5 block text-sm font-semibold">Judul / gagasan konten</span>
                                    <input wire:model="judulItem" type="text" autofocus placeholder="Apa yang ingin disampaikan?" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    @error('judulItem') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="mb-1.5 block text-sm font-semibold">Rencana kasar</span>
                                    <input wire:model="rencanaKasar" type="text" placeholder="Contoh: minggu ke-2 Agustus" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    <span class="mt-1 block text-xs text-stone-500">Bukan tanggal pasti. Jadwalkan ke Agenda saat waktunya sudah jelas.</span>
                                    @error('rencanaKasar') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="mb-1.5 block text-sm font-semibold">Jenis output</span>
                                    <select wire:model="jenisOutputId" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        <option value="">Pilih output</option>
                                        @foreach ($jenisOutput as $output)
                                            <option value="{{ $output->id }}">{{ $output->nama }}</option>
                                        @endforeach
                                    </select>
                                    @error('jenisOutputId') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <fieldset class="md:col-span-2">
                                    <legend class="mb-2 text-sm font-semibold">Kanal tujuan</legend>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($kanal as $tujuan)
                                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-stone-300 px-3 py-1.5 text-sm transition has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 has-[:checked]:text-orange-800 dark:border-zinc-700 dark:has-[:checked]:border-orange-600 dark:has-[:checked]:bg-orange-950/30 dark:has-[:checked]:text-orange-300">
                                                <input wire:model="kanalTujuan" type="checkbox" value="{{ $tujuan->id }}" class="rounded border-stone-300 text-orange-600 focus:ring-orange-500">
                                                {{ $tujuan->nama }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('kanalTujuan.*') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </fieldset>
                                <label class="md:col-span-2">
                                    <span class="mb-1.5 block text-sm font-semibold">Catatan editorial</span>
                                    <textarea wire:model="catatanItem" rows="3" placeholder="Sudut pandang, pesan kunci, atau bahan yang harus dicari" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"></textarea>
                                    @error('catatanItem') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                @if ($itemId)
                                    <label>
                                        <span class="mb-1.5 block text-sm font-semibold">Status item</span>
                                        <select wire:model="statusItem" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-orange-500 focus:ring-orange-500 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                            <option value="ide">Ide</option>
                                            <option value="diproduksi">Diproduksi</option>
                                            <option value="batal">Batal</option>
                                        </select>
                                    </label>
                                @endif
                                <div class="flex justify-end gap-2 md:col-span-2">
                                    <button wire:click="tutupSemuaForm" type="button" class="rounded-full border border-stone-300 px-4 py-2 text-sm font-semibold hover:bg-stone-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Batal</button>
                                    <button type="submit" class="rounded-full bg-orange-600 px-5 py-2 text-sm font-semibold text-white hover:bg-orange-500">Simpan item</button>
                                </div>
                            </form>
                        </section>
                    @endif

                    @if ($formJadwalTerbuka)
                        <section class="rounded-3xl border border-emerald-200 bg-emerald-50/70 p-5 dark:border-emerald-900 dark:bg-emerald-950/20 sm:p-6">
                            <div class="mb-5 flex items-start justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-emerald-700 dark:text-emerald-400">Kunci waktu</p>
                                    <h2 class="mt-1 text-lg font-semibold">Jadwalkan ke Agenda</h2>
                                    <p class="mt-1 text-sm text-stone-600 dark:text-zinc-400">Setelah ini, tanggal resmi dikelola dari Agenda.</p>
                                </div>
                                <button wire:click="tutupSemuaForm" type="button" aria-label="Tutup form jadwal" class="rounded-full p-2 text-stone-500 hover:bg-white dark:hover:bg-zinc-800">✕</button>
                            </div>
                            <form wire:submit="jadwalkanItem" class="grid gap-5 md:grid-cols-2">
                                <label>
                                    <span class="mb-1.5 block text-sm font-semibold">Mulai</span>
                                    <input wire:model="agendaMulaiAt" type="datetime-local" class="w-full rounded-xl border-emerald-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-emerald-900 dark:bg-zinc-950 dark:text-white">
                                    @error('agendaMulaiAt') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label>
                                    <span class="mb-1.5 block text-sm font-semibold">Selesai <span class="font-normal text-stone-400">(opsional)</span></span>
                                    <input wire:model="agendaSelesaiAt" type="datetime-local" class="w-full rounded-xl border-emerald-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-emerald-900 dark:bg-zinc-950 dark:text-white">
                                    @error('agendaSelesaiAt') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                </label>
                                <label class="md:col-span-2">
                                    <span class="mb-1.5 block text-sm font-semibold">Lokasi <span class="font-normal text-stone-400">(opsional)</span></span>
                                    <input wire:model="agendaLokasi" type="text" placeholder="Studio, kantor, atau lokasi kegiatan" class="w-full rounded-xl border-emerald-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-emerald-900 dark:bg-zinc-950 dark:text-white">
                                </label>
                                <div class="flex justify-end gap-2 md:col-span-2">
                                    <button wire:click="tutupSemuaForm" type="button" class="rounded-full border border-emerald-300 px-4 py-2 text-sm font-semibold hover:bg-white dark:border-emerald-900 dark:hover:bg-zinc-800">Batal</button>
                                    <button type="submit" class="rounded-full bg-emerald-700 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-600">Buat Agenda</button>
                                </div>
                            </form>
                        </section>
                    @endif

                    <section>
                        <div class="mb-3 flex items-end justify-between gap-3 px-1">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-[0.16em] text-stone-400">Antrean</p>
                                <h2 class="mt-1 text-lg font-semibold">Ide dan jadwal konten</h2>
                            </div>
                            <span class="text-xs text-stone-500">Tanggal pasti hanya di Agenda</span>
                        </div>
                        <div class="space-y-3">
                            @forelse ($planAktif->items->sortByDesc('created_at') as $item)
                                @php
                                    $statusClass = match ($item->status) {
                                        'dijadwalkan' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
                                        'diproduksi' => 'bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-300',
                                        'batal' => 'bg-stone-200 text-stone-600 dark:bg-zinc-800 dark:text-zinc-400',
                                        default => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
                                    };
                                    $kanalItem = collect($item->kanal_tujuan ?? [])->map(fn ($id) => $namaKanal->get($id))->filter();
                                @endphp
                                <article wire:key="item-{{ $item->id }}" class="group rounded-3xl border border-stone-200 bg-white p-4 shadow-sm transition hover:border-orange-200 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-orange-900 sm:p-5">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-wide {{ $statusClass }}">{{ $item->status }}</span>
                                                <span class="text-xs font-semibold text-stone-500 dark:text-zinc-400">{{ $item->jenisOutput->nama }}</span>
                                            </div>
                                            <h3 class="mt-3 text-base font-semibold leading-6 sm:text-lg">{{ $item->judul }}</h3>
                                            @if ($item->catatan)
                                                <p class="mt-1 line-clamp-2 text-sm leading-6 text-stone-600 dark:text-zinc-400">{{ $item->catatan }}</p>
                                            @endif
                                            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-stone-500 dark:text-zinc-400">
                                                <span class="inline-flex items-center gap-1.5"><span aria-hidden="true">◷</span> {{ $item->rencana_kasar ?: 'Waktu kasar belum ditentukan' }}</span>
                                                @if ($kanalItem->isNotEmpty())
                                                    <span>{{ $kanalItem->join(' · ') }}</span>
                                                @endif
                                                @if ($item->agenda_id)
                                                    <span class="font-semibold text-emerald-700 dark:text-emerald-400">Agenda #{{ $item->agenda_id }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex shrink-0 gap-2 sm:opacity-70 sm:transition sm:group-hover:opacity-100 sm:focus-within:opacity-100">
                                            <button wire:click="editItem({{ $item->id }})" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-bold hover:bg-stone-100 dark:border-zinc-700 dark:hover:bg-zinc-800">Ubah</button>
                                            @if (! $item->agenda_id && $item->status !== 'batal')
                                                <button wire:click="bukaJadwal({{ $item->id }})" class="rounded-full bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-600">Jadwalkan</button>
                                            @endif
                                            @if ($item->agenda_id && $item->status === 'dijadwalkan' && auth()->user()->can('kelola_konten'))
                                                <button wire:click="mulaiProduksi({{ $item->id }})" class="rounded-full bg-orange-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-orange-500">Mulai produksi</button>
                                            @elseif ($item->status === 'diproduksi' && auth()->user()->can('kelola_konten'))
                                                <a href="{{ route('produksi.index') }}" wire:navigate class="rounded-full bg-sky-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-sky-600">Buka produksi</a>
                                            @endif
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-14 text-center dark:border-zinc-700 dark:bg-zinc-900">
                                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-xl text-orange-700 dark:bg-orange-950 dark:text-orange-300">✦</div>
                                    <h3 class="mt-4 font-semibold">Antrean masih kosong</h3>
                                    <p class="mx-auto mt-1 max-w-sm text-sm text-stone-500">Tambahkan gagasan konten. Item belum akan muncul di papan produksi sebelum benar-benar digarap.</p>
                                    <button wire:click="tambahItem({{ $planAktif->id }})" class="mt-4 rounded-full bg-orange-600 px-4 py-2 text-sm font-semibold text-white hover:bg-orange-500">Tambah ide pertama</button>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @else
                    <section class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-20 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Mulai merencanakan</p>
                        <h2 class="mt-3 text-2xl font-semibold">Buat periode PR Plan pertama</h2>
                        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-stone-500">Tentukan periode, tema besar, dan target. Setelah itu isi antrean konten tanpa mengunci tanggal terlalu dini.</p>
                        <button wire:click="buatPlan" class="mt-5 rounded-full bg-stone-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-700 dark:bg-orange-500 dark:text-zinc-950">Buat PR Plan</button>
                    </section>
                @endif
            </main>
        </div>
    </div>
</div>
