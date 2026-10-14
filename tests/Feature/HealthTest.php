<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_endpoint_returns_the_documented_payload(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    public function test_undocumented_root_route_is_not_public(): void
    {
        $this->getJson('/')
            ->assertNotFound();
    }
}
