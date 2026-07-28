<div class="min-h-full bg-stone-50 text-stone-950 dark:bg-zinc-950 dark:text-white">
    <header class="relative overflow-hidden border-b border-stone-200 bg-[#10251f] text-white dark:border-zinc-800">
        <div class="absolute inset-0 opacity-30" style="background-image: linear-gradient(115deg, transparent 48%, rgba(52,211,153,.18) 48%, rgba(52,211,153,.18) 50%, transparent 50%), radial-gradient(circle at 12% 20%, #f97316 0, transparent 24%);"></div>
        <div class="relative mx-auto max-w-[1500px] px-5 py-9 sm:px-8 lg:px-10">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <div class="flex items-center gap-3 text-xs font-bold uppercase tracking-[0.22em] text-emerald-300"><span class="h-px w-8 bg-emerald-400"></span>Bukti kerja yang bertahan</div>
                    <h1 class="mt-4 text-3xl font-semibold tracking-[-0.04em] sm:text-4xl">Publikasi & Arsip</h1>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-stone-300 sm:text-base">Simpan tautan, kanal, PIC, dan tangkapan layar tayang dalam satu catatan sebelum pekerjaan resmi ditutup.</p>
                </div>
                <div class="flex gap-3">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 backdrop-blur">
                        <div class="text-2xl font-semibold">{{ $paketReview->count() }}</div>
                        <div class="text-[0.65rem] font-bold uppercase tracking-wide text-stone-400">Siap tayang</div>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-3 backdrop-blur">
                        <div class="text-2xl font-semibold">{{ $publikasi->count() }}</div>
                        <div class="text-[0.65rem] font-bold uppercase tracking-wide text-stone-400">Diarsipkan</div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="mx-auto max-w-[1500px] space-y-6 px-5 py-6 sm:px-8 lg:px-10">
        @if (session('publikasi-tersimpan'))
            <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-300">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-xs text-white">✓</span>{{ session('publikasi-tersimpan') }}
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-[minmax(280px,.62fr)_minmax(420px,1fr)_minmax(330px,.76fr)]">
            <section>
                <div class="mb-4 px-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-600">Review selesai</p>
                    <h2 class="mt-1 text-xl font-semibold">Siap dipublikasikan</h2>
                </div>
                <div class="space-y-3">
                    @forelse ($paketReview as $paket)
                        <button wire:key="review-{{ $paket->id }}" wire:click="pilihPaket({{ $paket->id }})" class="w-full rounded-2xl border bg-white p-4 text-left shadow-sm transition {{ $paketAktif?->id === $paket->id && ! $publikasiId ? 'border-orange-400 ring-2 ring-orange-100 dark:border-orange-600 dark:ring-orange-950' : 'border-stone-200 hover:border-orange-300 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-orange-900' }}">
                            <div class="flex items-center justify-between gap-3 text-[0.66rem] font-bold uppercase tracking-wide text-stone-400">
                                <span>Paket #{{ $paket->id }}</span><span>Revisi {{ $paket->revisi_ke }}</span>
                            </div>
                            <h3 class="mt-2 font-semibold leading-6">{{ $paket->judul }}</h3>
                            <p class="mt-2 text-xs text-stone-500">Review selesai {{ $paket->updated_at->diffForHumans() }}</p>
                        </button>
                    @empty
                        <div class="rounded-3xl border border-dashed border-stone-300 bg-white px-5 py-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">✓</div>
                            <h3 class="mt-4 font-semibold">Antrean bersih</h3>
                            <p class="mt-1 text-sm leading-6 text-stone-500">Belum ada paket berstatus Review yang menunggu publikasi.</p>
                        </div>
                    @endforelse
                </div>
            </section>

            <main>
                @if ($paketAktif)
                    <section class="overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-xl shadow-stone-950/5 dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="border-b border-stone-200 bg-stone-950 p-5 text-white dark:border-zinc-800 sm:p-6">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-400">{{ $publikasiId ? 'Perbarui jejak tayang' : 'Finalisasi publikasi' }}</p>
                                    <h2 class="mt-2 text-xl font-semibold leading-7">{{ $paketAktif->judul }}</h2>
                                </div>
                                @if ($publikasiId)
                                    <button wire:click="batalEdit" type="button" class="rounded-full border border-white/20 px-3 py-1.5 text-xs font-bold hover:bg-white/10">Tutup</button>
                                @endif
                            </div>
                        </div>

                        <form wire:submit="{{ $publikasiId ? 'simpanPerubahan' : 'simpanDanArsipkan' }}" class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                            <label>
                                <span class="mb-1.5 block text-sm font-semibold">Kanal</span>
                                <select wire:model="kanalId" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                    <option value="">Pilih kanal</option>
                                    @foreach ($kanal as $tujuan)
                                        <option value="{{ $tujuan->id }}">{{ $tujuan->nama }}</option>
                                    @endforeach
                                </select>
                                @error('kanalId') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label>
                                <span class="mb-1.5 block text-sm font-semibold">Waktu tayang</span>
                                <input wire:model="tayangAt" type="datetime-local" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                @error('tayangAt') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="sm:col-span-2">
                                <span class="mb-1.5 block text-sm font-semibold">URL publikasi</span>
                                <input wire:model="url" type="url" placeholder="https://instagram.com/p/..." class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-emerald-600 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                <span class="mt-1 block text-xs text-stone-500">Tanpa URL, kartu tidak dapat masuk arsip.</span>
                                @error('url') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>
                            <label class="sm:col-span-2">
                                <span class="mb-1.5 block text-sm font-semibold">Tangkapan layar tayang {{ $publikasiId ? '(opsional jika tidak diganti)' : '' }}</span>
                                <input wire:model="buktiTayang" type="file" accept="image/png,image/jpeg,image/webp" class="block w-full rounded-xl border border-stone-300 bg-stone-50 text-sm file:mr-4 file:border-0 file:bg-stone-950 file:px-4 file:py-2.5 file:font-semibold file:text-white dark:border-zinc-700 dark:bg-zinc-950 dark:file:bg-orange-500 dark:file:text-zinc-950">
                                <span class="mt-1 block text-xs text-stone-500">PNG, JPG, atau WebP · maksimal 5 MB.</span>
                                @error('buktiTayang') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                            </label>

                            @if ($publikasiId)
                                <label class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 sm:col-span-2 dark:border-amber-900 dark:bg-amber-950/30">
                                    <input wire:model.live="diubahSetelahTayang" type="checkbox" class="rounded border-stone-300 text-amber-600 focus:ring-amber-500">
                                    <span><span class="block text-sm font-semibold">Konten diubah setelah tayang</span><span class="text-xs text-stone-500">Aktifkan untuk revisi atas permintaan pimpinan.</span></span>
                                </label>
                                @if ($diubahSetelahTayang)
                                    <label class="sm:col-span-2">
                                        <span class="mb-1.5 block text-sm font-semibold">Alasan perubahan</span>
                                        <textarea wire:model="alasanPerubahan" rows="3" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white"></textarea>
                                        @error('alasanPerubahan') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                    <label class="sm:col-span-2">
                                        <span class="mb-1.5 block text-sm font-semibold">Diminta oleh</span>
                                        <input wire:model="dimintaOleh" type="text" placeholder="Nama atau jabatan" class="w-full rounded-xl border-stone-300 bg-white text-stone-950 focus:border-amber-600 focus:ring-amber-600 dark:border-zinc-700 dark:bg-zinc-950 dark:text-white">
                                        @error('dimintaOleh') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                                    </label>
                                @endif
                            @endif

                            <div class="flex items-center justify-between gap-4 border-t border-stone-200 pt-5 sm:col-span-2 dark:border-zinc-800">
                                <p class="max-w-xs text-xs leading-5 text-stone-500">Jejak ini menjadi sumber laporan dan bukti arsip, bukan sekadar checklist.</p>
                                <button type="submit" wire:loading.attr="disabled" class="rounded-full bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-600 disabled:opacity-50">{{ $publikasiId ? 'Simpan perubahan' : 'Catat & arsipkan' }}</button>
                            </div>
                        </form>
                    </section>
                @else
                    <section class="rounded-3xl border border-dashed border-stone-300 bg-white px-6 py-20 text-center dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 text-xl text-orange-700 dark:bg-orange-950 dark:text-orange-300">↗</div>
                        <h2 class="mt-4 font-semibold">Pilih paket siap tayang</h2>
                        <p class="mx-auto mt-1 max-w-sm text-sm leading-6 text-stone-500">Form publikasi terbuka setelah memilih pekerjaan dari antrean Review.</p>
                    </section>
                @endif
            </main>

            <aside>
                <div class="mb-4 px-1">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-stone-400">Jejak tersimpan</p>
                    <h2 class="mt-1 text-xl font-semibold">Arsip terbaru</h2>
                </div>
                <div class="space-y-3">
                    @forelse ($publikasi as $tayang)
                        <article wire:key="publikasi-{{ $tayang->id }}" class="rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">{{ $tayang->kanal->nama }}</span>
                                <span class="text-xs text-stone-400">{{ $tayang->tayang_at->translatedFormat('d M Y') }}</span>
                            </div>
                            <h3 class="mt-3 font-semibold leading-6">{{ $namaPaket->get($tayang->paket_konten_id, 'Paket konten') }}</h3>
                            @if ($tayang->diubah_setelah_tayang)
                                <p class="mt-2 text-xs font-semibold text-amber-700 dark:text-amber-400">Pernah direvisi setelah tayang</p>
                            @endif
                            <div class="mt-4 flex items-center justify-between gap-3 border-t border-stone-100 pt-3 dark:border-zinc-800">
                                <a href="{{ $tayang->url }}" target="_blank" rel="noopener" class="max-w-36 truncate text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">Buka publikasi ↗</a>
                                <button wire:click="editPublikasi({{ $tayang->id }})" class="rounded-full border border-stone-300 px-3 py-1.5 text-xs font-bold hover:bg-stone-50 dark:border-zinc-700 dark:hover:bg-zinc-800">Ubah catatan</button>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-stone-300 px-5 py-10 text-center text-sm text-stone-400 dark:border-zinc-700">Belum ada publikasi yang diarsipkan.</div>
                    @endforelse
                </div>
            </aside>
        </div>
    </div>
</div>
