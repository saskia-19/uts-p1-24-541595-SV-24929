<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_rejects_invalid_credentials(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertStatus(401);
    }

    public function test_items_endpoint_requires_authentication(): void
    {
        $response = $this->getJson('/api/items');

        $response->assertUnauthorized();
    }
}