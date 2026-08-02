<?php

namespace Tests\Feature\Settings;

use App\Livewire\PengaturanAi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Ai\Contracts\PenyediaAi;
use Modules\Ai\Models\KonfigurasiAi;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Tests\TestCase;

class PengaturanAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hanya_pengguna_dengan_izin_kelola_ai_yang_dapat_membuka_pengaturan(): void
    {
        $tanpaIzin = $this->pengguna('tanpa-izin-ai@example.com');
        $admin = $this->pengguna('admin-ai@example.com', true);

        $this->actingAs($tanpaIzin)->get(route('settings.ai'))->assertForbidden();
        $this->actingAs($admin)->get(route('settings.ai'))->assertOk()->assertSee('AI dan Mexia');
    }

    public function test_superadmin_dapat_menyimpan_konfigurasi_dan_api_key_terenkripsi(): void
    {
        $admin = $this->pengguna('simpan-ai@example.com', true);

        Livewire::actingAs($admin)
            ->test(PengaturanAi::class)
            ->set('provider', 'openai_compatible')
            ->set('baseUrl', 'https://router.mexia.me/v1/')
            ->set('apiKey', 'mexia-secret-test-key')
            ->set('model', 'provider/model-berita')
            ->set('timeout', 120)
            ->set('promptVersi', 'berita-atensi-v2')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSet('apiKey', '')
            ->assertSet('apiKeyTersimpan', true)
            ->assertSee('Pengaturan AI berhasil disimpan.');

        $konfigurasi = KonfigurasiAi::sole();
        $rawKey = KonfigurasiAi::query()->getQuery()->where('id', $konfigurasi->id)->value('api_key');

        $this->assertSame('https://router.mexia.me/v1', $konfigurasi->base_url);
        $this->assertSame('mexia-secret-test-key', $konfigurasi->api_key);
        $this->assertNotSame('mexia-secret-test-key', $rawKey);
        $this->assertSame($admin->id, $konfigurasi->diubah_oleh);
    }

    public function test_konfigurasi_database_langsung_dipakai_provider(): void
    {
        $admin = $this->pengguna('provider-ai@example.com', true);
        KonfigurasiAi::create([
            'provider' => 'openai_compatible',
            'base_url' => 'https://router.mexia.me/v1',
            'api_key' => 'mexia-database-key',
            'model' => 'provider/model-berita',
            'timeout' => 90,
            'prompt_versi' => 'berita-atensi-v2',
            'diubah_oleh' => $admin->id,
        ]);
        Http::fake([
            'https://router.mexia.me/v1/chat/completions' => Http::response([
                'model' => 'provider/model-berita',
                'choices' => [['message' => ['content' => 'BANDUNG - Naskah uji.']]],
            ]),
        ]);

        $hasil = app(PenyediaAi::class)->hasilkan('ringkasan', 'Judul uji', ['Sumber uji']);

        $this->assertSame('BANDUNG - Naskah uji.', $hasil->isi);
        Http::assertSent(fn ($request) => $request->url() === 'https://router.mexia.me/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer mexia-database-key')
            && $request['model'] === 'provider/model-berita');
    }

    public function test_menghapus_api_key_sekaligus_menonaktifkan_ai(): void
    {
        $admin = $this->pengguna('hapus-ai@example.com', true);
        KonfigurasiAi::create([
            'provider' => 'openai_compatible',
            'base_url' => 'https://router.mexia.me/v1',
            'api_key' => 'key-yang-dihapus',
            'model' => 'provider/model-berita',
            'timeout' => 90,
            'prompt_versi' => 'berita-atensi-v1',
            'diubah_oleh' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(PengaturanAi::class)
            ->call('hapusApiKey')
            ->assertSet('provider', 'nonaktif')
            ->assertSet('apiKeyTersimpan', false);

        $this->assertNull(KonfigurasiAi::sole()->api_key);
        $this->assertSame('nonaktif', KonfigurasiAi::sole()->provider);
        $this->assertFalse(app(PenyediaAi::class)->tersedia());
    }

    public function test_menonaktifkan_provider_tidak_menghapus_api_key_tanpa_perintah_hapus(): void
    {
        $admin = $this->pengguna('nonaktif-ai@example.com', true);
        KonfigurasiAi::create([
            'provider' => 'openai_compatible',
            'base_url' => 'https://router.mexia.me/v1',
            'api_key' => 'key-yang-tetap-tersimpan',
            'model' => 'provider/model-berita',
            'timeout' => 90,
            'prompt_versi' => 'berita-atensi-v1',
            'diubah_oleh' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(PengaturanAi::class)
            ->set('provider', 'nonaktif')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSet('apiKeyTersimpan', true);

        $this->assertSame('nonaktif', KonfigurasiAi::sole()->provider);
        $this->assertSame('key-yang-tetap-tersimpan', KonfigurasiAi::sole()->api_key);
    }

    private function pengguna(string $email, bool $denganIzin = false): User
    {
        $role = Role::create(['nama' => $email, 'slug' => str($email)->before('@')->slug()]);

        if ($denganIzin) {
            $role->permissions()->attach(Permission::create(['nama' => 'Kelola AI', 'slug' => 'kelola_ai']));
        }

        return User::create([
            'nama' => $email,
            'email' => $email,
            'password' => 'password',
            'role_id' => $role->id,
            'status' => 'aktif',
        ]);
    }
}
