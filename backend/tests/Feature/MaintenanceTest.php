<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Equipment;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $apiKey = explode(',', config('app.api_keys'))[0];
        $this->headers = ['X-API-Key' => $apiKey];

        // Create user and get token
        $user = User::factory()->create(['email' => 'tech@test.com', 'password' => bcrypt('password')]);
        $token = $user->createToken('test')->plainTextToken;
        $this->headers['Authorization'] = 'Bearer ' . $token;
    }

    public function test_equipment_lifecycle()
    {
        // 1. Create Equipment
        $payload = [
            'name' => 'CNC Machine 01',
            'code' => 'EQ-CNC-01',
            'status' => 'operational',
            'location' => 'Zone A'
        ];
        $response = $this->withHeaders($this->headers)->postJson('/api/equipment', $payload);
        $response->assertStatus(201);
        $eqId = $response->json('id');

        // 2. Schedule Maintenance
        $schedulePayload = [
            'equipment_id' => $eqId,
            'name' => 'Weekly Inspection',
            'trigger_type' => 'time',
            'interval_days' => 7,
            'next_maintenance' => now()->addDays(7)->toDateString()
        ];
        $this->withHeaders($this->headers)->postJson('/api/maintenance/schedules', $schedulePayload)
            ->assertStatus(201);

        // 3. Report Broken (Create Unplanned Request)
        $this->withHeaders($this->headers)->postJson("/api/equipment/{$eqId}/report-broken", [
            'description' => 'Motor overheating',
            'priority' => 'high'
        ])->assertStatus(200)
            ->assertJsonFragment(['status' => 'confirmed']) // Request status (was broken check, but returns equipment + request)
            ->assertJsonFragment(['request_type' => 'corrective']); // Request type

        // 4. Update Status (Equipment -> maintenance)
        $this->assertDatabaseHas('equipment', ['id' => $eqId, 'status' => 'broken']);

        // 5. Complete Maintenance Request (Fix)
        $requestId = \App\Models\MaintenanceRequest::where('equipment_id', $eqId)->first()->id;
        $this->withHeaders($this->headers)->putJson("/api/maintenance/requests/{$requestId}", [
            'status' => 'done',
            'resolution_notes' => 'Replaced fan'
        ])->assertStatus(200);

        // 6. Verify Equipment Active Again
        $this->assertDatabaseHas('equipment', ['id' => $eqId, 'status' => 'operational']);
    }
}
