<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

final class PanelLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_filament_login_authenticates_an_administrator(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = User::factory()->create([
            'is_admin' => true,
            'password' => Hash::make('phase-3-test-password'),
        ]);

        Livewire::test(Login::class)
            ->set('data.email', $admin->email)
            ->set('data.password', 'phase-3-test-password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_filament_login_does_not_authenticate_a_non_administrator(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $user = User::factory()->create([
            'is_admin' => false,
            'password' => Hash::make('phase-3-test-password'),
        ]);

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'phase-3-test-password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }
}
