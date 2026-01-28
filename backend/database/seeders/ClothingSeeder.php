<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use App\Models\Product;
use App\Models\Bom;
use App\Models\WorkCenter;
use App\Models\ManufacturingOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Services\ManufacturingOrderService;
use App\Services\OeeService;
use Illuminate\Support\Facades\Auth;

class ClothingSeeder extends Seeder
{
    private $orgId = 1;
    private $user;
    private $whLoc;
    private $cutting;
    private $sewing;
    private $cottonFabric;
    private $thread;
    private $buttons;
    private $tshirt;
    private $jeans;
    private $bom;
    private $sewOp;
    private $check1;

    public function run(): void
    {
        $this->truncateTables();
        $this->createOrganizationAndUser();
        $this->createRawMaterials();
        $this->createLocations();
        $this->seedInitialStock();
        $this->createFinishedGoods();
        $this->createBomAndOperations();
        $this->createEquipmentAndMaintenance();
        $this->createManufacturingOrders();
        $this->createJeansProduct();
    }

    private function truncateTables(): void
    {
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Bom::truncate();
        \App\Models\BomLine::truncate();
        DB::table('operations')->truncate();
        ManufacturingOrder::truncate();
        \App\Models\WorkOrder::truncate();
        \App\Models\Consumption::truncate();
        \App\Models\Stock::truncate();
        \App\Models\Lot::truncate();
        \App\Models\Serial::truncate();
        \App\Models\Equipment::truncate();
        \App\Models\MaintenanceRequest::truncate();
        \App\Models\MaintenanceSchedule::truncate();
        \App\Models\CostEntry::truncate();
        \App\Models\OeeRecord::truncate();
        \App\Models\StockAdjustment::truncate();
        \App\Models\Scrap::truncate();
        \App\Models\UnbuildOrder::truncate();
        \App\Models\TimeLog::truncate();
        Schema::enableForeignKeyConstraints();
    }

    private function createOrganizationAndUser(): void
    {
        Organization::firstOrCreate(['id' => $this->orgId], ['name' => 'Demo Clothing Co.', 'slug' => 'demo-clothing']);

        $this->user = User::where('email', 'admin@example.com')->first();
        if (!$this->user) {
            $this->user = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'organization_id' => $this->orgId
            ]);
        }

        // Log in for Seeder actions
        Auth::login($this->user);
    }

    private function createRawMaterials(): void
    {
        $this->command->info('Creating Raw Materials...');

        $this->cottonFabric = Product::create([
            'name' => 'Cotton Fabric Roll',
            'code' => 'RM-FAB-COT',
            'type' => 'raw',
            'uom' => 'm',
            'cost' => 5.00,
            'tracking' => 'lot',
            'organization_id' => $this->orgId,
            'image_url' => 'products/cotton_fabric.png'
        ]);

        $this->thread = Product::create([
            'name' => 'Sewing Thread',
            'code' => 'RM-THREAD',
            'type' => 'raw',
            'uom' => 'm',
            'cost' => 0.05,
            'tracking' => 'none',
            'organization_id' => $this->orgId,
            'image_url' => 'products/thread.png'
        ]);

        $this->buttons = Product::create([
            'name' => 'Standard Button',
            'code' => 'RM-BUTTON',
            'type' => 'raw',
            'uom' => 'pcs',
            'cost' => 0.10,
            'tracking' => 'none',
            'organization_id' => $this->orgId,
            'image_url' => 'products/buttons.png'
        ]);
    }

    private function createLocations(): void
    {
        $this->whLoc = \App\Models\Location::firstOrCreate(
            ['name' => 'Warehouse'],
            ['type' => 'warehouse', 'organization_id' => $this->orgId, 'code' => 'WH-MAIN']
        );
        \App\Models\Location::firstOrCreate(
            ['name' => 'Production Line'],
            ['type' => 'production', 'organization_id' => $this->orgId, 'code' => 'WH-PROD']
        );
        \App\Models\Location::firstOrCreate(
            ['name' => 'Quality Inspection'],
            ['type' => 'internal', 'organization_id' => $this->orgId, 'code' => 'WH-QA']
        );
        \App\Models\Location::firstOrCreate(
            ['name' => 'Empty Storage Room'],
            ['type' => 'warehouse', 'organization_id' => $this->orgId, 'code' => 'WH-EMPTY']
        );
    }

    private function seedInitialStock(): void
    {
        $fabricLot = 'LOT-COT-001';
        $this->addStock($this->cottonFabric, $this->whLoc, 5000, $fabricLot);
        $this->addStock($this->thread, $this->whLoc, 100000);
        $this->addStock($this->buttons, $this->whLoc, 20000);
    }

    private function createFinishedGoods(): void
    {
        $this->command->info('Creating Finished Goods...');

        $this->tshirt = Product::create([
            'name' => 'Basic T-Shirt (White / M)',
            'code' => 'FG-TEE-WHT-M',
            'type' => 'finished',
            'uom' => 'pcs',
            'tracking' => 'lot',
            'description' => 'Standard crew neck t-shirt',
            'organization_id' => $this->orgId,
            'image_url' => 'products/tshirt_white.png'
        ]);

        $this->jeans = Product::create([
            'name' => 'Denim Jeans (Blue / 32)',
            'code' => 'FG-JEANS-BLU-32',
            'type' => 'finished',
            'uom' => 'pcs',
            'tracking' => 'serial',
            'description' => 'Straight cut denim jeans',
            'organization_id' => $this->orgId,
            'image_url' => 'products/jeans_blue.png'
        ]);
    }

    private function createBomAndOperations(): void
    {
        $this->cutting = WorkCenter::firstOrCreate(['name' => 'Cutting Station'], ['code' => 'WC-CUT', 'cost_per_hour' => 20, 'organization_id' => $this->orgId]);
        $this->sewing = WorkCenter::firstOrCreate(['name' => 'Sewing Station'], ['code' => 'WC-SEW', 'cost_per_hour' => 15, 'organization_id' => $this->orgId]);

        $this->command->info("Creating BoM for {$this->tshirt->name}...");
        $this->bom = Bom::create([
            'product_id' => $this->tshirt->id,
            'organization_id' => $this->orgId,
            'qty_produced' => 1,
            'type' => 'normal'
        ]);

        $this->bom->lines()->create(['product_id' => $this->cottonFabric->id, 'quantity' => 1.2]);
        $this->bom->lines()->create(['product_id' => $this->thread->id, 'quantity' => 10]);

        $this->bom->operations()->create(['sequence' => 10, 'name' => 'Cut Fabric', 'work_center_id' => $this->cutting->id, 'duration_minutes' => 10]);
        $this->bom->operations()->create([
            'sequence' => 20,
            'name' => 'Sew Assembly',
            'work_center_id' => $this->sewing->id,
            'duration_minutes' => 25,
            'needs_quality_check' => true
        ]);
    }



    private function createEquipmentAndMaintenance(): void
    {
        $cuttingMachine = \App\Models\Equipment::create([
            'name' => 'Fabric Cutter Pro X1',
            'code' => 'EQ-CUT-01',
            'work_center_id' => $this->cutting->id,
            'status' => 'operational',
            'organization_id' => $this->orgId,
            'maintenance_interval_days' => 30,
            'last_maintenance' => now()->subDays(10),
            'next_maintenance' => now()->addDays(20),
        ]);
        $sewingMachine = \App\Models\Equipment::create([
            'name' => 'Juki Sewing Station',
            'code' => 'EQ-SEW-01',
            'work_center_id' => $this->sewing->id,
            'status' => 'maintenance',
            'organization_id' => $this->orgId,
            'maintenance_interval_days' => 90,
            'last_maintenance' => now()->subDays(100),
            'next_maintenance' => now()->subDays(10),
        ]);

        \App\Models\MaintenanceSchedule::create([
            'organization_id' => $this->orgId,
            'equipment_id' => $cuttingMachine->id,
            'name' => 'Monthly Calibration',
            'trigger_type' => 'time',
            'interval_days' => 30,
            'last_maintenance' => now()->subDays(30),
            'next_maintenance' => now(),
            'instructions' => 'Calibrate laser guide',
            'is_active' => true,
        ]);
        \App\Models\MaintenanceRequest::create([
            'name' => 'MR-2026-00001',
            'equipment_id' => $sewingMachine->id,
            'request_type' => 'corrective',
            'priority' => 'high',
            'status' => 'in_progress',
            'description' => 'Needle alignment issue',
            'diagnosis' => 'Worn out needle bar',
            'requested_by' => $this->user->id,
            'organization_id' => $this->orgId,
            'scheduled_date' => now(),
        ]);
    }



    private function createManufacturingOrders(): void
    {
        $this->command->info('Creating 10 Mixed Status Manufacturing Orders...');
        $moService = app(ManufacturingOrderService::class);
        $oeeService = app(OeeService::class);

        // Ensure BOM has QA check on both operations for visibility
        $this->bom->operations()->update(['needs_quality_check' => true]);

        $statuses = ['draft', 'confirmed', 'scheduled', 'in_progress', 'done'];

        for ($i = 0; $i < 10; $i++) {
            $statusIndex = intdiv($i, 2); // 0,0, 1,1, 2,2, 3,3, 4,4
            $targetStatus = $statuses[$statusIndex] ?? 'draft';

            // Create MO
            $mo = $moService->create([
                'organization_id' => $this->orgId,
                'product_id' => $this->tshirt->id,
                'bom_id' => $this->bom->id,
                'qty_to_produce' => 10 + ($i * 5), // Varying quantity
                'priority' => ($i % 3 == 0) ? 'high' : 'normal',
                'scheduled_start' => now()->addDays($i),
                'scheduled_end' => now()->addDays($i + 1),
            ]);

            // Advance status
            if ($targetStatus !== 'draft') {
                $moService->confirm($mo);
            }

            if ($targetStatus === 'scheduled') {
                $moService->schedule($mo, [
                    'scheduled_start' => now()->addDays(1),
                    'scheduled_end' => now()->addDays(2)
                ]);
            }

            if ($targetStatus === 'in_progress') {
                $moService->start($mo);

                // Complete first work order for one of them
                if ($i % 2 == 0) {
                    $wo = $mo->workOrders()->first();
                    $wo->update([
                        'status' => 'done',
                        'started_at' => now()->subHours(2),
                        'finished_at' => now()->subHours(1),
                        'duration_actual' => 60,
                        'qa_status' => 'pass', // Auto pass for this demo
                        'qa_comments' => 'Routine check passed',
                        'qa_by' => $this->user->id,
                        'qa_at' => now()->subMinutes(30)
                    ]);
                }
            }

            if ($targetStatus === 'done') {
                $moService->start($mo);

                // Finish all work orders
                foreach ($mo->workOrders as $wo) {
                    $wo->update([
                        'status' => 'done',
                        'started_at' => now()->subHours(5),
                        'finished_at' => now()->subHours(4),
                        'duration_actual' => $wo->duration_expected,
                        'quantity_produced' => $mo->qty_to_produce // Match produced
                    ]);

                    // Random QA
                    if ($wo->operation->needs_quality_check) {
                        $pass = rand(0, 1) === 1;
                        // Leave one without QA record to test "needs QA" button
                        if ($i == 9 && $wo->sequence == 20) {
                            // Do nothing, leave QA null
                        } else {
                            $wo->update([
                                'qa_status' => $pass ? 'pass' : 'fail',
                                'qa_comments' => $pass ? 'Good' : 'Bad quality',
                                'qa_by' => $this->user->id,
                                'qa_at' => now()->subMinutes(120)
                            ]);
                        }
                    }
                }

                // Complete MO
                $moService->complete($mo, [
                    'qty_produced' => $mo->qty_to_produce,
                    'location_id' => $this->whLoc->id
                ]);
            }
        }
    }

    private function createJeansProduct(): void
    {
        if ($this->jeans) {
            $bomJeans = Bom::create(['product_id' => $this->jeans->id, 'organization_id' => $this->orgId, 'qty_produced' => 1, 'type' => 'normal']);
            $denim = Product::firstOrCreate(['name' => 'Denim Fabric', 'organization_id' => $this->orgId], ['code' => 'RM-DENIM', 'type' => 'raw', 'uom' => 'm', 'cost' => 8.00, 'tracking' => 'serial', 'image_url' => 'products/denim_fabric.png']);

            $this->addStock($denim, $this->whLoc, 2000);

            $bomJeans->lines()->create(['product_id' => $denim->id, 'quantity' => 1.5]);
            $bomJeans->lines()->create(['product_id' => $this->thread->id, 'quantity' => 20]);
            $bomJeans->lines()->create(['product_id' => $this->buttons->id, 'quantity' => 4]);
            $bomJeans->operations()->create(['sequence' => 10, 'name' => 'Cut Pattern', 'work_center_id' => $this->cutting->id, 'duration_minutes' => 15, 'needs_quality_check' => true]);
            $bomJeans->operations()->create(['sequence' => 20, 'name' => 'Heavy Sewing', 'work_center_id' => $this->sewing->id, 'duration_minutes' => 40, 'needs_quality_check' => true]);
        }
    }

    private function addStock($product, $location, $qty, $lotNum = null)
    {
        $lotId = null;
        if ($lotNum) {
            $lot = \App\Models\Lot::firstOrCreate(
                ['product_id' => $product->id, 'name' => $lotNum],
                ['organization_id' => $product->organization_id, 'manufactured_date' => now()]
            );
            $lotId = $lot->id;
        }

        \App\Models\Stock::create([
            'organization_id' => $product->organization_id,
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => $qty,
            'lot_id' => $lotId
        ]);
    }
}
