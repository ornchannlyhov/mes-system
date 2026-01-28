<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];
    }

    public function test_full_production_cycle()
    {
        // 1. Auth: Register and Login
        $userPayload = [
            'name' => 'Test Operator',
            'email' => 'operator@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'operator'
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/auth/register', $userPayload);
        $response->assertStatus(200);

        // Retrieve OTP
        $attempt = \App\Models\RegistrationAttempt::where('email', 'operator@test.com')->first();
        $this->assertNotNull($attempt, 'Registration attempt not found');

        // Verify Email
        $verifyPayload = [
            'email' => 'operator@test.com',
            'code' => $attempt->otp
        ];

        $verifyResp = $this->withHeaders($this->headers)->postJson('/api/auth/verify-email', $verifyPayload);
        $verifyResp->assertStatus(200);
        $token = $verifyResp->json('token');

        $this->headers['Authorization'] = 'Bearer ' . $token;

        // Verify token is set
        $this->assertNotEmpty($token, 'Token should not be empty');

        // 2. Engineering: Create Components and Product
        $materialPayload = [
            'code' => 'WOOD-001',
            'name' => 'Oak Wood',
            'type' => 'raw',
            'tracking' => 'none',
            'uom' => 'unit',
            'description' => 'Raw material'
        ];
        $materialResp = $this->withHeaders($this->headers)->postJson('/api/products', $materialPayload);
        $materialResp->assertStatus(201);
        $materialId = $materialResp->json('id');

        $productPayload = [
            'code' => 'TABLE-001',
            'name' => 'Oak Table',
            'type' => 'finished',
            'tracking' => 'lot',
            'uom' => 'unit',
            'description' => 'Finished good'
        ];
        $productResp = $this->withHeaders($this->headers)->postJson('/api/products', $productPayload);
        $productResp->assertStatus(201);
        $productId = $productResp->json('id');

        // 3. Engineering: Create BOM
        // Adjust stock first so we can produce
        $locationPayload = ['name' => 'Warehouse A', 'code' => 'WH-A'];
        $locResp = $this->withHeaders($this->headers)->postJson('/api/locations', $locationPayload);
        $locResp->assertStatus(201);
        $locationId = $locResp->json('id');

        // Add lots/stock for material
        $this->withHeaders($this->headers)->postJson("/api/locations/{$locationId}/adjust-stock", [
            'product_id' => $materialId,
            'quantity' => 100,
            'type' => 'add',
            'reason' => 'Initial Stock'
        ])->assertStatus(200);

        // Create BOM
        $bomPayload = [
            'product_id' => $productId,
            'qty_produced' => 1,
            'lines' => [
                ['product_id' => $materialId, 'quantity' => 4] // 4 legs/wood units per table
            ]
        ];
        $bomResp = $this->withHeaders($this->headers)->postJson('/api/boms', $bomPayload);
        $bomResp->assertStatus(201);
        $bomId = $bomResp->json('id');

        // 4. Execution: Create Manufacturing Order
        $moPayload = [
            'product_id' => $productId,
            'bom_id' => $bomId,
            'qty_to_produce' => 10,
            'priority' => 'normal',
            'scheduled_end' => now()->addDays(7)->toDateString()
        ];
        $moResp = $this->withHeaders($this->headers)->postJson('/api/manufacturing-orders', $moPayload);
        $moResp->assertStatus(201);
        $moId = $moResp->json('id');

        // 5. Execution: Confirm MO (Checks stock)
        $this->withHeaders($this->headers)->postJson("/api/manufacturing-orders/{$moId}/confirm")
            ->assertStatus(200)
            ->assertJson(['status' => 'confirmed']);

        // 6. Execution: Start MO
        $this->withHeaders($this->headers)->postJson("/api/manufacturing-orders/{$moId}/start")
            ->assertStatus(200)
            ->assertJson(['status' => 'in_progress']);

        // 7. Execution: Complete MO
        $this->withHeaders($this->headers)->postJson("/api/manufacturing-orders/{$moId}/complete", [
            'qty_produced' => 10,
            'location_id' => $locationId
        ])
            ->assertStatus(200)
            ->assertJson(['status' => 'done']);

        // 8. Verification: Check Stock of Finished Good
        $this->withHeaders($this->headers)->getJson("/api/locations/{$locationId}/stock")
            ->assertStatus(200)
            ->assertJsonFragment(['product_id' => $productId])
            ->assertJsonFragment(['quantity' => '10.0000']);
    }
}
