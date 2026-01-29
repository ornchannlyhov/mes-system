<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Bom;
use App\Models\WorkCenter;

class EngineeringTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];

        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;
    }

    // ========== PRODUCTS ==========

    public function test_product_crud_lifecycle()
    {
        // Create
        $payload = [
            'name' => 'Test Product',
            'code' => 'TP-001',
            'type' => 'finished',
            'tracking' => 'none',
            'uom' => 'unit',
            'description' => 'A test product',
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/products', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Product']);
        $productId = $response->json('data.id');

        // Read
        $this->withHeaders($this->headers)->getJson("/api/products/{$productId}")
            ->assertStatus(200)
            ->assertJsonFragment(['code' => 'TP-001']);

        // Update
        $this->withHeaders($this->headers)->putJson("/api/products/{$productId}", [
            'name' => 'Updated Product',
            'code' => 'TP-001',
            'type' => 'finished',
            'tracking' => 'none',
            'uom' => 'unit',
        ])->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Product']);

        // List
        $this->withHeaders($this->headers)->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonFragment(['code' => 'TP-001']);

        // Delete
        $this->withHeaders($this->headers)->deleteJson("/api/products/{$productId}")
            ->assertStatus(204);

        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    public function test_product_validation_rules()
    {
        // Missing required fields
        $response = $this->withHeaders($this->headers)->postJson('/api/products', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'type', 'uom']);

        // Invalid type
        $response = $this->withHeaders($this->headers)->postJson('/api/products', [
            'name' => 'Test',
            'code' => 'T1',
            'type' => 'invalid_type',
            'uom' => 'unit',
        ]);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    // ========== BOMs ==========

    public function test_bom_crud_with_lines()
    {
        $product = Product::create([
            'name' => 'Finished Good',
            'code' => 'FG-001',
            'type' => 'finished',
            'uom' => 'unit',
        ]);

        $component = Product::create([
            'name' => 'Component',
            'code' => 'COMP-001',
            'type' => 'component',
            'uom' => 'unit',
        ]);

        // Create BOM with lines
        $payload = [
            'product_id' => $product->id,
            'qty_produced' => 1,
            'lines' => [
                ['product_id' => $component->id, 'quantity' => 2],
            ],
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/boms', $payload);
        $response->assertStatus(201);
        $bomId = $response->json('data.id');

        // Read with lines
        $this->withHeaders($this->headers)->getJson("/api/boms/{$bomId}")
            ->assertStatus(200)
            ->assertJsonFragment(['product_id' => $product->id]);

        // Update
        $this->withHeaders($this->headers)->putJson("/api/boms/{$bomId}", [
            'product_id' => $product->id,
            'qty_produced' => 2,
        ])->assertStatus(200);

        // List
        $this->withHeaders($this->headers)->getJson('/api/boms')
            ->assertStatus(200);

        // Delete
        $this->withHeaders($this->headers)->deleteJson("/api/boms/{$bomId}")
            ->assertStatus(204);
    }

    // ========== WORK CENTERS ==========

    public function test_work_center_crud()
    {
        // Create
        $payload = [
            'name' => 'Assembly Line 1',
            'code' => 'WC-ASM-01',
            'capacity_per_hour' => 100,
            'cost_per_hour' => 50.00,
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/work-centers', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Assembly Line 1']);
        $wcId = $response->json('data.id');

        // Read
        $this->withHeaders($this->headers)->getJson("/api/work-centers/{$wcId}")
            ->assertStatus(200);

        // Update
        $this->withHeaders($this->headers)->putJson("/api/work-centers/{$wcId}", [
            'name' => 'Assembly Line 1 Updated',
            'code' => 'WC-ASM-01',
            'capacity_per_hour' => 120,
            'cost_per_hour' => 55.00,
        ])->assertStatus(200)
            ->assertJsonFragment(['name' => 'Assembly Line 1 Updated']);

        // List
        $this->withHeaders($this->headers)->getJson('/api/work-centers')
            ->assertStatus(200);

        // Delete
        $this->withHeaders($this->headers)->deleteJson("/api/work-centers/{$wcId}")
            ->assertStatus(204);
    }

    public function test_work_center_validation()
    {
        $response = $this->withHeaders($this->headers)->postJson('/api/work-centers', []);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code']);
    }
}
