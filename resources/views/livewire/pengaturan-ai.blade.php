<div data-proud-page>
  <main class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <header>
        <p class="text-xs">Pengaturan superadmin</p>
        <h1>AI dan Mexia</h1>
        <p class="mt-2 max-w-2xl text-sm">Kelola endpoint, API key, model, dan batas waktu AI tanpa mengubah file server.</p>
    </header>

    @if (session('ai-pengaturan-tersimpan'))
        <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200">
            {{ session('ai-pengaturan-tersimpan') }}
        </div>
    @endif

    <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-lg font-semibold">Koneksi penyedia</h2>
                <p class="mt-1 text-sm text-zinc-500">API key dienkripsi di database dan tidak pernah ditampilkan kembali setelah disimpan.</p>
            </div>
            <span class="inline-flex w-fit rounded-full border px-2.5 py-1 text-xs font-semibold {{ $siapDigunakan ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-300' : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-300' }}">
                {{ $siapDigunakan ? 'Konfigurasi lengkap' : 'Belum aktif' }}
            </span>
        </div>

        <form wire:submit="simpan" class="mt-6 space-y-5">
            <label class="block text-sm font-semibold">
                Provider
                <select wire:model.live="provider" class="w-full">
                    <option value="nonaktif">Nonaktif</option>
                    <option value="openai_compatible">OpenAI-compatible / Mexia</option>
                </select>
                @error('provider') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            @if ($siapDigunakan)
                <p class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200">
                    Endpoint, API key, dan model sudah terisi. Koneksi sebenarnya akan diuji saat fitur AI digunakan.
                </p>
            @endif

            <label class="block text-sm font-semibold">
                Base URL
                <input wire:model="baseUrl" type="url" placeholder="https://router.mexia.me/v1" class="w-full">
                <span class="mt-1 block text-xs font-normal text-zinc-500">Untuk Mexia gunakan <code>https://router.mexia.me/v1</code>.</span>
                @error('baseUrl') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <label class="block text-sm font-semibold">
                API key
                <input wire:model="apiKey" type="password" autocomplete="new-password" placeholder="{{ $apiKeyTersimpan ? 'Tersimpan, kosongkan jika tidak ingin mengganti' : 'Masukkan API key Mexia' }}" class="w-full">
                <span class="mt-1 block text-xs font-normal text-zinc-500">{{ $apiKeyTersimpan ? 'API key sudah tersimpan secara terenkripsi. Menonaktifkan provider tidak akan menghapusnya.' : 'API key belum tersimpan.' }}</span>
                @error('apiKey') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="block text-sm font-semibold">
                    Model
                    <input wire:model="model" type="text" placeholder="ID model dari Mexia" class="w-full">
                    <span class="mt-1 block text-xs font-normal text-zinc-500">Gunakan ID persis dari katalog Mexia yang mendukung chat completions.</span>
                    @error('model') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>

                <label class="block text-sm font-semibold">
                    Timeout
                    <div class="flex items-center gap-2"><input wire:model="timeout" type="number" min="10" max="300" class="w-full"><span class="text-sm text-zinc-500">detik</span></div>
                    @error('timeout') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
                </label>
            </div>

            <label class="block text-sm font-semibold">
                Versi prompt
                <input wire:model="promptVersi" type="text" class="w-full">
                <span class="mt-1 block text-xs font-normal text-zinc-500">Label audit untuk melacak prompt yang menghasilkan suatu usulan.</span>
                @error('promptVersi') <span class="mt-1 block text-sm text-red-600">{{ $message }}</span> @enderror
            </label>

            <div class="flex flex-col-reverse gap-2 border-t border-zinc-200 pt-5 dark:border-zinc-800 sm:flex-row sm:justify-end">
                @if ($apiKeyTersimpan)
                    <button type="button" wire:click="hapusApiKey" wire:confirm="Hapus API key dan nonaktifkan AI?" class="min-h-11 rounded-lg border border-red-300 bg-white px-4 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:bg-zinc-900 dark:text-red-300 dark:hover:bg-red-950">Hapus API key</button>
                @endif
                <button type="submit" wire:loading.attr="disabled" wire:target="simpan" class="min-h-11 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50">
                    <span wire:loading.remove wire:target="simpan">Simpan pengaturan</span>
                    <span wire:loading wire:target="simpan">Menyimpan…</span>
                </button>
            </div>
        </form>
    </section>
  </main>
</div>
