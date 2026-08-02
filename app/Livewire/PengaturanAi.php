<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\Models\KonfigurasiAi;
use Modules\Ai\Services\KonfigurasiAiAktif;

#[Layout('components.layouts.app')]
class PengaturanAi extends Component
{
    public string $provider = 'nonaktif';

    public string $baseUrl = 'https://router.mexia.me/v1';

    public string $apiKey = '';

    public string $model = '';

    public int $timeout = 90;

    public string $promptVersi = 'berita-atensi-v1';

    public bool $apiKeyTersimpan = false;

    public function mount(KonfigurasiAiAktif $konfigurasi): void
    {
        Gate::authorize('kelola_ai');
        $nilai = $konfigurasi->get();

        $this->provider = $nilai['provider'];
        $this->baseUrl = $nilai['base_url'];
        $this->model = $nilai['model'] ?? '';
        $this->timeout = $nilai['timeout'];
        $this->promptVersi = $nilai['prompt_versi'];
        $this->apiKeyTersimpan = filled($nilai['api_key']);
    }

    public function simpan(): void
    {
        Gate::authorize('kelola_ai');
        $data = $this->validate([
            'provider' => ['required', Rule::in(['nonaktif', 'openai_compatible'])],
            'baseUrl' => ['required_if:provider,openai_compatible', 'nullable', 'url:http,https', 'max:500'],
            'apiKey' => [$this->apiKeyTersimpan ? 'nullable' : 'required_if:provider,openai_compatible', 'string', 'max:4000'],
            'model' => ['required_if:provider,openai_compatible', 'nullable', 'string', 'max:255'],
            'timeout' => ['required', 'integer', 'min:10', 'max:300'],
            'promptVersi' => ['required', 'string', 'max:100'],
        ]);

        $konfigurasi = KonfigurasiAi::query()->latest('id')->first() ?? new KonfigurasiAi;
        $konfigurasi->fill([
            'provider' => $data['provider'],
            'base_url' => filled($data['baseUrl']) ? rtrim($data['baseUrl'], '/') : null,
            'model' => $data['model'] ?: null,
            'timeout' => $data['timeout'],
            'prompt_versi' => $data['promptVersi'],
            'diubah_oleh' => Auth::id(),
        ]);

        if (filled($data['apiKey'])) {
            $konfigurasi->api_key = $data['apiKey'];
        }

        $konfigurasi->save();
        $this->apiKey = '';
        $this->apiKeyTersimpan = filled($konfigurasi->api_key);
        session()->flash('ai-pengaturan-tersimpan', 'Pengaturan AI berhasil disimpan.');
    }

    public function hapusApiKey(): void
    {
        Gate::authorize('kelola_ai');
        $konfigurasi = KonfigurasiAi::query()->latest('id')->firstOrFail();
        $konfigurasi->update(['api_key' => null, 'provider' => 'nonaktif', 'diubah_oleh' => Auth::id()]);

        $this->provider = 'nonaktif';
        $this->apiKey = '';
        $this->apiKeyTersimpan = false;
        session()->flash('ai-pengaturan-tersimpan', 'API key dihapus dan AI dinonaktifkan.');
    }

    public function render()
    {
        Gate::authorize('kelola_ai');

        return view('livewire.pengaturan-ai', [
            'siapDigunakan' => app(PenyediaAi::class)->tersedia(),
        ]);
    }
}
