<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Bom;
use App\Models\ManufacturingOrder;

class ManufacturingOrderDateResetTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $apiKey = '1234567890abcdef';
        config(['app.api_keys' => $apiKey]);

        $this->user = User::factory()->create();
        $this->headers = [
            'X-API-Key' => $apiKey,
            'Authorization' => 'Bearer ' . $this->user->createToken('test')->plainTextToken
        ];
    }

    public function test_reset_scheduled_order_preserves_dates()
    {
        // 1. Create dependencies manually
        $product = Product::create([
            'name' => 'FP-DATE-TEST',
            'code' => 'FP-DATE',
            'type' => 'finished',
        ]);

        $bom = Bom::create([
            'product_id' => $product->id,
            'code' => 'BOM-DATE',
            'qty_produced' => 1,
            'version' => '1.0',
            'is_active' => true,
        ]);

        $startDate = now()->startOfHour();
        $endDate = now()->addDays(2)->startOfHour();

        // 2. Create Scheduled Order
        $mo = ManufacturingOrder::create([
            'name' => 'MO-DATE-RESET',
            'product_id' => $product->id,
            'bom_id' => $bom->id,
            'qty_to_produce' => 5,
            'priority' => 'high',
            'status' => 'scheduled',
            'scheduled_start' => $startDate,
            'scheduled_end' => $endDate,
        ]);

        // 3. Perform Reset
        $response = $this->withHeaders($this->headers)
            ->postJson("/api/manufacturing-orders/{$mo->id}/reset");

        $response->assertStatus(200);

        // 4. Verify status is draft but dates are preserved
        $mo->refresh();
        $this->assertEquals('draft', $mo->status);
        $this->assertEquals($startDate->toDateTimeString(), $mo->scheduled_start->toDateTimeString());
        $this->assertEquals($endDate->toDateTimeString(), $mo->scheduled_end->toDateTimeString());
    }
}
