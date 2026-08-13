<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_does_not_create_accounts_or_content(): void
    {
        $this->seed();

        $this->assertDatabaseCount('users', 0);
    }
}
