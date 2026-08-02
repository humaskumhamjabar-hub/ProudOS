<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\People\Models\Permission;
use Modules\People\Models\Role;
use Modules\People\Models\User;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_beranda(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_dashboard_redirects_to_beranda(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertRedirect('/');
    }

    public function test_sidebar_groups_navigation_for_an_authorized_user(): void
    {
        $role = Role::create(['nama' => 'Administrator', 'slug' => 'admin-sidebar']);
        $permissions = collect([
            'kelola_pengguna',
            'kelola_agenda',
            'kelola_tugas',
            'kelola_pr_plan',
            'kelola_konten',
            'kelola_template_visual',
            'upload_publikasi',
            'kelola_monitoring',
            'lihat_laporan',
        ])->map(fn (string $slug) => Permission::create(['nama' => $slug, 'slug' => $slug]));
        $role->permissions()->attach($permissions);
        $user = User::factory()->create(['role_id' => $role->id, 'status' => 'aktif']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('collapsible="true"', false);
        $response->assertSee('data-flux-sidebar-collapse', false);
        $response->assertSee('data-flux-sidebar-group', false);
        $response->assertSeeTextInOrder([
            'Pekerjaan Saya',
            'Beranda',
            'Tugas Saya',
            'Papan Kanban',
            'Kalender',
            'Perencanaan',
            'Kelola Agenda',
            'Kelola Tugas',
            'PR Plan',
            'Produksi & Tayang',
            'Meja Produksi',
            'Studio Carousel',
            'Studio Video',
            'Publikasi & Arsip',
            'Pemantauan',
            'Monitoring',
            'Pusat Laporan',
            'Referensi & Pengaturan',
            'Pustaka',
            'Template Visual',
            'Kelola Tim',
        ]);
    }

    public function test_sidebar_hides_empty_permission_groups_for_a_basic_user(): void
    {
        $role = Role::create(['nama' => 'Pengguna Dasar', 'slug' => 'pengguna-dasar-sidebar']);
        $user = User::factory()->create(['role_id' => $role->id, 'status' => 'aktif']);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSeeText('Pekerjaan Saya');
        $response->assertSeeText('Referensi & Pengaturan');
        $response->assertSeeText('Pustaka');
        $response->assertDontSeeText('Perencanaan');
        $response->assertDontSeeText('Produksi & Tayang');
        $response->assertDontSeeText('Pemantauan');
    }
}
