<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;

class TraceabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];
    }

    public function test_lot_and_serial_crud()
    {
        $user = User::factory()->create(['email' => 'trace@test.com']);
        $token = $user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;

        $product = Product::create([
            'name' => 'Tracked Part',
            'code' => 'P-TRACK',
            'type' => 'component',
            'tracking' => 'lot',
            'uom' => 'unit'
        ]);

        // 1. Create Lot
        $lotPayload = [
            'product_id' => $product->id,
            'name' => 'LOT-001',
            'expiry_date' => now()->addYear()->toDateString()
        ];
        $this->withHeaders($this->headers)->postJson('/api/lots', $lotPayload)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'LOT-001']);

        // 2. Create Serial (for a serialized product)
        $serializedProduct = Product::create([
            'name' => 'Serialized Part',
            'code' => 'P-SERIAL',
            'type' => 'component',
            'tracking' => 'serial',
            'uom' => 'unit'
        ]);

        $serialPayload = [
            'product_id' => $serializedProduct->id,
            'name' => 'SN-001',
            'status' => 'active'
        ];
        $this->withHeaders($this->headers)->postJson('/api/serials', $serialPayload)
            ->assertStatus(201)
            ->assertJsonFragment(['name' => 'SN-001']);

        // 3. Serial Genealogy (Basic check)
        // Get ID first
        $serialId = \App\Models\Serial::where('name', 'SN-001')->first()->id;
        $this->withHeaders($this->headers)->getJson("/api/serials/{$serialId}/genealogy")
            ->assertStatus(200);
    }
}
