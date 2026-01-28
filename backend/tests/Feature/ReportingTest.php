<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Bom;
use App\Models\Location;
use App\Models\WorkCenter;
use App\Models\ManufacturingOrder;
use App\Models\CostEntry;
use App\Models\OeeRecord;

class ReportingTest extends TestCase
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

    public function test_cost_analysis_for_manufacturing_order()
    {
        $product = Product::create([
            'name' => 'Costed Product',
            'code' => 'CP-001',
            'type' => 'finished',
            'uom' => 'unit',
            'cost' => 100,
        ]);

        $bom = Bom::create([
            'product_id' => $product->id,
            'code' => 'BOM-CP',
            'qty_produced' => 1,
            'is_active' => true,
        ]);

        $mo = ManufacturingOrder::create([
            'name' => 'MO-COST-TEST',
            'product_id' => $product->id,
            'bom_id' => $bom->id,
            'qty_to_produce' => 10,
            'status' => 'done',
        ]);

        // Create cost entries with correct schema
        CostEntry::create([
            'manufacturing_order_id' => $mo->id,
            'cost_type' => 'material',
            'total_cost' => 500,
        ]);

        CostEntry::create([
            'manufacturing_order_id' => $mo->id,
            'cost_type' => 'labor',
            'total_cost' => 200,
        ]);

        // Get cost analysis
        $response = $this->withHeaders($this->headers)->getJson("/api/reporting/cost/{$mo->id}");
        $response->assertStatus(200);
    }

    public function test_oee_records_retrieval()
    {
        $workCenter = WorkCenter::create([
            'name' => 'OEE Test Center',
            'code' => 'OEE-WC',
            'capacity_per_hour' => 100,
            'cost_per_hour' => 50,
        ]);

        // Create OEE records with correct schema
        OeeRecord::create([
            'work_center_id' => $workCenter->id,
            'record_date' => today(),
            'planned_time_minutes' => 480,
            'actual_runtime_minutes' => 456,
            'downtime_minutes' => 24,
            'ideal_cycle_time' => 0.5,
            'total_units_produced' => 800,
            'good_units' => 784,
            'defect_units' => 16,
            'availability_score' => 95,
            'performance_score' => 90,
            'quality_score' => 98,
            'oee_score' => 83.79,
        ]);

        OeeRecord::create([
            'work_center_id' => $workCenter->id,
            'record_date' => today()->subDay(),
            'planned_time_minutes' => 480,
            'actual_runtime_minutes' => 440,
            'downtime_minutes' => 40,
            'ideal_cycle_time' => 0.5,
            'total_units_produced' => 750,
            'good_units' => 727,
            'defect_units' => 23,
            'availability_score' => 92,
            'performance_score' => 88,
            'quality_score' => 97,
            'oee_score' => 78.54,
        ]);

        // Get OEE records
        $response = $this->withHeaders($this->headers)->getJson('/api/reporting/oee');
        $response->assertStatus(200);
    }
}
