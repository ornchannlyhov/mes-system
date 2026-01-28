<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Bom;
use App\Models\Location;
use App\Models\Stock;
use App\Models\WorkCenter;
use App\Models\Operation;
use App\Models\ManufacturingOrder;
use App\Models\WorkOrder;

class ExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;
    protected $product;
    protected $bom;
    protected $location;
    protected $workCenter;
    protected $operation;
    protected $component;
    protected $user;
    protected $organizationId;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];

        $this->user = User::factory()->create();
        $this->organizationId = $this->user->organization_id;
        $token = $this->user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;

        // Setup common fixtures
        $this->product = Product::create([
            'name' => 'Finished Product',
            'code' => 'FP-001',
            'type' => 'finished',
            'uom' => 'unit',
        ]);

        $this->component = Product::create([
            'name' => 'Component',
            'code' => 'COMP-001',
            'type' => 'component',
            'uom' => 'unit',
        ]);

        $this->workCenter = WorkCenter::create([
            'name' => 'Production Line',
            'code' => 'PL-01',
            'capacity_per_hour' => 10,
            'cost_per_hour' => 25,
        ]);

        $this->bom = Bom::create([
            'product_id' => $this->product->id,
            'code' => 'BOM-FP-001',
            'qty_produced' => 1,
            'version' => '1.0',
            'is_active' => true,
        ]);

        $this->bom->lines()->create([
            'product_id' => $this->component->id,
            'quantity' => 2,
        ]);

        // Create operation for the BOM
        $this->operation = Operation::create([
            'bom_id' => $this->bom->id,
            'work_center_id' => $this->workCenter->id,
            'name' => 'Assembly',
            'sequence' => 1,
            'duration_minutes' => 30,
        ]);

        $this->location = Location::create(['name' => 'Main Warehouse', 'code' => 'MW']);

        // Add component stock
        Stock::create([
            'location_id' => $this->location->id,
            'product_id' => $this->component->id,
            'quantity' => 1000,
            'reserved_qty' => 0,
        ]);
    }

    // ========== MANUFACTURING ORDERS ==========

    public function test_manufacturing_order_full_lifecycle()
    {
        // Create MO
        $moPayload = [
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 5,
            'priority' => 'normal',
            'scheduled_end' => now()->addDays(7)->toDateString(),
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/manufacturing-orders', $moPayload);
        $response->assertStatus(201);
        $moId = $response->json('id');

        // Confirm
        $this->withHeaders($this->headers)->postJson("/api/manufacturing-orders/{$moId}/confirm")
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'confirmed']);

        // Start
        $this->withHeaders($this->headers)->postJson("/api/manufacturing-orders/{$moId}/start")
            ->assertStatus(200)
            ->assertJsonFragment(['status' => 'in_progress']);

        // Complete
        $this->withHeaders($this->headers)->postJson("/api/manufacturing-orders/{$moId}/complete", [
            'qty_produced' => 5,
            'location_id' => $this->location->id,
        ])->assertStatus(200)
            ->assertJsonFragment(['status' => 'done']);

        // Verify stock of finished product increased
        $this->assertDatabaseHas('stocks', [
            'location_id' => $this->location->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_manufacturing_order_crud()
    {
        // List
        $this->withHeaders($this->headers)->getJson('/api/manufacturing-orders')
            ->assertStatus(200);

        // Create
        $mo = ManufacturingOrder::create([
            'name' => 'MO-TEST',
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 10,
            'status' => 'draft',
        ]);

        // Show
        $this->withHeaders($this->headers)->getJson("/api/manufacturing-orders/{$mo->id}")
            ->assertStatus(200);

        // Update
        $this->withHeaders($this->headers)->putJson("/api/manufacturing-orders/{$mo->id}", [
            'qty_to_produce' => 15,
        ])->assertStatus(200);

        // Delete
        $this->withHeaders($this->headers)->deleteJson("/api/manufacturing-orders/{$mo->id}")
            ->assertStatus(204);
    }

    // ========== WORK ORDERS ==========

    public function test_work_order_crud()
    {
        $mo = ManufacturingOrder::create([
            'name' => 'MO-WO-TEST',
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 5,
            'status' => 'in_progress',
        ]);

        // Create Work Order via API
        $woPayload = [
            'manufacturing_order_id' => $mo->id,
            'operation_id' => $this->operation->id,
            'work_center_id' => $this->workCenter->id,
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/work-orders', $woPayload);
        $response->assertStatus(201);
        $woId = $response->json('id');

        // Read
        $this->withHeaders($this->headers)->getJson("/api/work-orders/{$woId}")
            ->assertStatus(200);

        // Update
        $this->withHeaders($this->headers)->putJson("/api/work-orders/{$woId}", [
            'notes' => 'Updated notes',
        ])->assertStatus(200);

        // List
        $this->withHeaders($this->headers)->getJson('/api/work-orders')
            ->assertStatus(200);

        // Delete
        $this->withHeaders($this->headers)->deleteJson("/api/work-orders/{$woId}")
            ->assertStatus(204);
    }

    public function test_work_order_lifecycle()
    {
        $mo = ManufacturingOrder::create([
            'name' => 'MO-WO-LIFECYCLE',
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 5,
            'status' => 'in_progress',
        ]);

        $wo = WorkOrder::create([
            'manufacturing_order_id' => $mo->id,
            'operation_id' => $this->operation->id,
            'work_center_id' => $this->workCenter->id,
            'status' => 'ready',
        ]);

        // Start
        $this->withHeaders($this->headers)->postJson("/api/work-orders/{$wo->id}/start")
            ->assertStatus(200);

        // Pause
        $this->withHeaders($this->headers)->postJson("/api/work-orders/{$wo->id}/pause")
            ->assertStatus(200);

        // Resume
        $this->withHeaders($this->headers)->postJson("/api/work-orders/{$wo->id}/resume")
            ->assertStatus(200);

        // Finish
        $this->withHeaders($this->headers)->postJson("/api/work-orders/{$wo->id}/finish", [
            'quantity_produced' => 5,
        ])->assertStatus(200);

        $this->assertDatabaseHas('work_orders', [
            'id' => $wo->id,
            'status' => 'done',
        ]);
    }

    // ========== SCRAP ==========

    public function test_scrap_crud()
    {
        $mo = ManufacturingOrder::create([
            'name' => 'MO-SCRAP-TEST',
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 10,
            'status' => 'in_progress',
        ]);

        $wo = WorkOrder::create([
            'manufacturing_order_id' => $mo->id,
            'operation_id' => $this->operation->id,
            'work_center_id' => $this->workCenter->id,
            'status' => 'in_progress',
        ]);

        // Add stock for the product so scrap can be recorded
        Stock::updateOrCreate(
            ['location_id' => $this->location->id, 'product_id' => $this->product->id],
            ['quantity' => 100, 'reserved_qty' => 0]
        );

        // Create scrap
        $scrapPayload = [
            'work_order_id' => $wo->id,
            'product_id' => $this->product->id,
            'location_id' => $this->location->id,
            'quantity' => 2,
            'reason' => 'Defective material',
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/scraps', $scrapPayload);
        $response->assertStatus(201);

        // List scraps
        $this->withHeaders($this->headers)->getJson('/api/scraps')
            ->assertStatus(200);
    }

    // ========== UNBUILD ORDERS ==========

    public function test_unbuild_order_creation()
    {
        // First create some finished product stock
        Stock::create([
            'location_id' => $this->location->id,
            'product_id' => $this->product->id,
            'quantity' => 50,
            'reserved_qty' => 0,
        ]);

        $unbuildPayload = [
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'quantity' => 5,
            'location_id' => $this->location->id,
        ];

        $response = $this->withHeaders($this->headers)->postJson('/api/unbuild-orders', $unbuildPayload);
        $response->assertStatus(201);
        $unbuildId = $response->json('id');

        // List
        $this->withHeaders($this->headers)->getJson('/api/unbuild-orders')
            ->assertStatus(200);

        // Show
        $this->withHeaders($this->headers)->getJson("/api/unbuild-orders/{$unbuildId}")
            ->assertStatus(200);
    }

    public function test_scheduling_draft_mo_reserves_stock_and_creates_work_orders()
    {
        // 1. Create a draft MO
        $mo = ManufacturingOrder::create([
            'name' => 'MO-SCHED-TEST',
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 10,
            'status' => 'draft',
            'organization_id' => $this->organizationId,
        ]);

        // Manually create consumptions (usually done in service->create, but we're testing scheduler)
        $this->bom->lines->each(function ($line) use ($mo) {
            $mo->consumptions()->create([
                'product_id' => $line->product_id,
                'qty_planned' => $line->quantity * $mo->qty_to_produce / $this->bom->qty_produced,
                'qty_consumed' => 0,
            ]);
        });

        // 2. Call the reschedule endpoint
        $payload = [
            'scheduled_start' => now()->addDay()->toDateTimeString(),
            'scheduled_end' => now()->addDay()->addHour()->toDateTimeString(),
        ];

        $response = $this->withHeaders($this->headers)
            ->putJson("/api/manufacturing-orders/{$mo->id}/reschedule", $payload);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'scheduled']);

        // 3. Verify status is scheduled
        $this->assertEquals('scheduled', $mo->fresh()->status);

        // 4. Verify work_orders table has entries
        $this->assertDatabaseHas('work_orders', [
            'manufacturing_order_id' => $mo->id,
            'operation_id' => $this->operation->id,
        ]);

        // 5. Verify stock is reserved
        // Component quantity was 1000, 2 per unit * 10 units = 20 should be reserved
        $this->assertDatabaseHas('stocks', [
            'product_id' => $this->component->id,
            'reserved_qty' => 20,
        ]);
    }

    public function test_auto_start_console_command()
    {
        // 1. Create a scheduled MO with start date in the past
        $mo = ManufacturingOrder::create([
            'name' => 'MO-PAST-TEST',
            'product_id' => $this->product->id,
            'bom_id' => $this->bom->id,
            'qty_to_produce' => 5,
            'status' => 'scheduled',
            'organization_id' => $this->organizationId,
            'scheduled_start' => now()->subHour(),
            'scheduled_end' => now()->addHour(),
        ]);

        // Create work orders (since it's already scheduled, we assume they exist)
        $mo->workOrders()->create([
            'operation_id' => $this->operation->id,
            'work_center_id' => $this->workCenter->id,
            'sequence' => 1,
            'status' => 'pending',
        ]);

        // 2. Run the command
        $this->artisan('app:start-scheduled-orders')
            ->expectsOutput("Found 1 orders to start.")
            ->expectsOutput("Started MO: {$mo->name}")
            ->assertExitCode(0);

        // 3. Verify status is in_progress
        $this->assertEquals('in_progress', $mo->fresh()->status);
        $this->assertNotNull($mo->fresh()->actual_start);
    }
}
