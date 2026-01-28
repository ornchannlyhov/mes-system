<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Location;
use App\Models\Stock;

class InventoryTest extends TestCase
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

    // ========== LOCATIONS ==========

    public function test_location_crud()
    {
        // Create
        $payload = [
            'name' => 'Warehouse A',
            'code' => 'WH-A',
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/locations', $payload);
        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Warehouse A']);
        $locationId = $response->json('id');

        // Read
        $this->withHeaders($this->headers)->getJson("/api/locations/{$locationId}")
            ->assertStatus(200);

        // Update
        $this->withHeaders($this->headers)->putJson("/api/locations/{$locationId}", [
            'name' => 'Warehouse A Updated',
            'code' => 'WH-A',
        ])->assertStatus(200);

        // List
        $this->withHeaders($this->headers)->getJson('/api/locations')
            ->assertStatus(200);

        // Delete
        $this->withHeaders($this->headers)->deleteJson("/api/locations/{$locationId}")
            ->assertStatus(204);
    }

    public function test_location_stock_adjustment()
    {
        $location = Location::create(['name' => 'Test Location', 'code' => 'TL-01']);
        $product = Product::create([
            'name' => 'Stock Item',
            'code' => 'SI-001',
            'type' => 'component',
            'uom' => 'unit',
        ]);

        // Add stock
        $this->withHeaders($this->headers)->postJson("/api/locations/{$location->id}/adjust-stock", [
            'product_id' => $product->id,
            'quantity' => 100,
            'type' => 'add',
            'reason' => 'Initial stock',
        ])->assertStatus(200);

        $this->assertDatabaseHas('stocks', [
            'location_id' => $location->id,
            'product_id' => $product->id,
        ]);

        // Check stock
        $this->withHeaders($this->headers)->getJson("/api/locations/{$location->id}/stock")
            ->assertStatus(200)
            ->assertJsonFragment(['product_id' => $product->id]);

        // Remove stock
        $this->withHeaders($this->headers)->postJson("/api/locations/{$location->id}/adjust-stock", [
            'product_id' => $product->id,
            'quantity' => 50,
            'type' => 'subtract',
            'reason' => 'Adjustment',
        ])->assertStatus(200);
    }

    // ========== STOCK MOVEMENTS ==========

    public function test_stock_transfer_between_locations()
    {
        $sourceLocation = Location::create(['name' => 'Source', 'code' => 'SRC']);
        $destLocation = Location::create(['name' => 'Destination', 'code' => 'DST']);
        $product = Product::create([
            'name' => 'Transfer Item',
            'code' => 'TI-001',
            'type' => 'component',
            'uom' => 'unit',
        ]);

        // Add initial stock to source
        Stock::create([
            'location_id' => $sourceLocation->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'reserved_qty' => 0,
        ]);

        // Transfer
        $this->withHeaders($this->headers)->postJson('/api/stock/transfer', [
            'product_id' => $product->id,
            'from_location_id' => $sourceLocation->id,
            'to_location_id' => $destLocation->id,
            'quantity' => 30,
        ])->assertStatus(200);

        // Verify source reduced
        $this->assertDatabaseHas('stocks', [
            'location_id' => $sourceLocation->id,
            'product_id' => $product->id,
            'quantity' => 70,
        ]);

        // Verify destination increased
        $this->assertDatabaseHas('stocks', [
            'location_id' => $destLocation->id,
            'product_id' => $product->id,
            'quantity' => 30,
        ]);
    }

    public function test_stock_reserve_and_release()
    {
        $location = Location::create(['name' => 'Reserve Location', 'code' => 'RL']);
        $product = Product::create([
            'name' => 'Reserve Item',
            'code' => 'RI-001',
            'type' => 'component',
            'uom' => 'unit',
        ]);

        Stock::create([
            'location_id' => $location->id,
            'product_id' => $product->id,
            'quantity' => 100,
            'reserved_qty' => 0,
        ]);

        // Reserve
        $this->withHeaders($this->headers)->postJson('/api/stock/reserve', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 25,
        ])->assertStatus(200);

        $this->assertDatabaseHas('stocks', [
            'location_id' => $location->id,
            'product_id' => $product->id,
            'reserved_qty' => 25,
        ]);

        // Release
        $this->withHeaders($this->headers)->postJson('/api/stock/release', [
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 25,
        ])->assertStatus(200);
    }

    public function test_transfer_insufficient_stock_fails()
    {
        $sourceLocation = Location::create(['name' => 'Low Stock', 'code' => 'LS']);
        $destLocation = Location::create(['name' => 'Dest', 'code' => 'D']);
        $product = Product::create([
            'name' => 'Low Stock Item',
            'code' => 'LSI-001',
            'type' => 'component',
            'uom' => 'unit',
        ]);

        Stock::create([
            'location_id' => $sourceLocation->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'reserved_qty' => 0,
        ]);

        // Try to transfer more than available
        $this->withHeaders($this->headers)->postJson('/api/stock/transfer', [
            'product_id' => $product->id,
            'from_location_id' => $sourceLocation->id,
            'to_location_id' => $destLocation->id,
            'quantity' => 100,
        ])->assertStatus(422);
    }
}
