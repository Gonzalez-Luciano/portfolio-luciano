<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class BootstrapAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_the_first_administrator_without_exposing_its_password(): void
    {
        $password = 'phase-3-plaintext-sentinel';
        $logPath = storage_path('logs/bootstrap-admin-test.log');
        @unlink($logPath);
        config([
            'logging.default' => 'single',
            'logging.channels.single.path' => $logPath,
        ]);
        $this->artisan('portfolio:bootstrap-admin')
            ->expectsQuestion('Name', 'Luciano Gonzalez')
            ->expectsQuestion('Email', 'luciano@example.test')
            ->expectsQuestion('Password', $password)
            ->expectsQuestion('Confirm password', $password)
            ->doesntExpectOutputToContain($password)
            ->assertExitCode(0);

        $admin = User::query()->sole();

        $this->assertSame('Luciano Gonzalez', $admin->name);
        $this->assertSame('luciano@example.test', $admin->email);
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check($password, $admin->password));
        $this->assertStringNotContainsString($password, file_exists($logPath) ? (string) file_get_contents($logPath) : '');
    }

    public function test_command_does_not_create_a_user_when_prompt_validation_fails(): void
    {
        $this->artisan('portfolio:bootstrap-admin')
            ->expectsQuestion('Name', '')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_refuses_mismatched_password_confirmation_without_creating_a_user(): void
    {
        $this->artisan('portfolio:bootstrap-admin')
            ->expectsQuestion('Name', 'Luciano Gonzalez')
            ->expectsQuestion('Email', 'luciano@example.test')
            ->expectsQuestion('Password', 'phase-3-test-password')
            ->expectsQuestion('Confirm password', 'different-password')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_refuses_an_existing_email_without_updating_it(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $originalPassword = $user->password;

        $this->artisan('portfolio:bootstrap-admin')
            ->expectsQuestion('Name', 'Replacement Name')
            ->expectsQuestion('Email', $user->email)
            ->expectsQuestion('Password', 'phase-3-test-password')
            ->expectsQuestion('Confirm password', 'phase-3-test-password')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => $user->name,
            'is_admin' => false,
            'password' => $originalPassword,
        ]);
    }

    public function test_command_refuses_to_create_a_second_administrator(): void
    {
        User::factory()->create(['is_admin' => true]);

        $this->artisan('portfolio:bootstrap-admin')
            ->expectsOutput('An administrator already exists. No user was created.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_command_refuses_to_run_while_another_database_connection_holds_the_bootstrap_lock(): void
    {
        config(['database.connections.bootstrap_lock' => config('database.connections.mysql')]);

        $connection = DB::connection('bootstrap_lock');
        $acquired = $connection->selectOne('SELECT GET_LOCK(?, 0) AS acquired', ['portfolio:bootstrap-admin']);

        $this->assertSame(1, (int) $acquired->acquired);

        try {
            $this->artisan('portfolio:bootstrap-admin')
                ->expectsOutput('Administrator bootstrap is already in progress. No user was created.')
                ->assertExitCode(1);
        } finally {
            $connection->selectOne('SELECT RELEASE_LOCK(?) AS released', ['portfolio:bootstrap-admin']);
            DB::purge('bootstrap_lock');
        }

        $this->assertDatabaseCount('users', 0);
    }
}
