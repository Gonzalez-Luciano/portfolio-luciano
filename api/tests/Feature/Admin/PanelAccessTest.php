<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_panel_redirects_guests_to_the_filament_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('filament.admin.auth.login'));
    }

    public function test_user_factory_defaults_to_a_non_administrator(): void
    {
        $this->assertFalse(User::factory()->make()->is_admin);
    }

    public function test_is_admin_cannot_be_set_through_mass_assignment(): void
    {
        $user = User::query()->create([
            'name' => 'Unprivileged User',
            'email' => 'unprivileged@example.test',
            'password' => 'phase-3-test-password',
            'is_admin' => true,
        ]);

        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_non_administrator_is_denied_the_admin_panel(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_administrator_can_reach_the_admin_panel(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}
