<?php

namespace Tests\Feature;

use Tests\TestCase;

final class HealthTest extends TestCase
{
    public function test_up_reports_application_boot_without_database_query(): void
    {
        $this->get('/up')->assertOk();
    }
}
