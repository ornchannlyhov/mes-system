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

/**
 * UAT demo data for Prestige Table Works Co.
 * Covers every system feature: products, BOMs, operations, work centers, equipment,
 * maintenance (schedules/requests/logs), manufacturing orders (all statuses),
 * work orders with QA, consumptions, lots, serials, stock adjustments, scrap,
 * unbuild orders, cost entries, and OEE records.
 */
class TableManufacturingSeeder extends Seeder
{
    private int $orgId = 1;
    private User $user;

    // Locations
    private \App\Models\Location $locRM;
    private \App\Models\Location $locProd;
    private \App\Models\Location $locQA;
    private \App\Models\Location $locFG;
    private \App\Models\Location $locScrap;

    // Work Centers
    private WorkCenter $wcCut;
    private WorkCenter $wcCNC;
    private WorkCenter $wcAssm;
    private WorkCenter $wcSand;
    private WorkCenter $wcQA;

    // Raw Material Products
    private Product $oakBoard;
    private Product $pinepanel;
    private Product $steelTube;
    private Product $mdfBoard;
    private Product $screwM6;
    private Product $cornerBracket;
    private Product $woodVarnish;
    private Product $sandpaper;

    // Finished Goods
    private Product $diningTable;
    private Product $coffeeTable;
    private Product $officeDesk;
    private Product $patioTable;

    // BOMs
    private Bom $bomDining;
    private Bom $bomCoffee;
    private Bom $bomDesk;

    public function run(): void
    {
        $this->truncateTables();
        $this->createOrganizationAndUser();
        $this->createLocations();
        $this->createWorkCenters();
        $this->createRawMaterials();
        $this->createFinishedGoods();
        $this->seedInitialStock();
        $this->createStockAdjustments();
        $this->createBomsAndOperations();
        $this->createEquipmentAndMaintenance();
        $this->createManufacturingOrders();
        $this->createScrapRecords();
        $this->createUnbuildOrder();
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
        \App\Models\MaintenanceLog::truncate();
        \App\Models\CostEntry::truncate();
        \App\Models\OeeRecord::truncate();
        \App\Models\StockAdjustment::truncate();
        \App\Models\Scrap::truncate();
        \App\Models\UnbuildOrder::truncate();
        Schema::enableForeignKeyConstraints();
    }

    private function createOrganizationAndUser(): void
    {
        Organization::firstOrCreate(
            ['id' => $this->orgId],
            ['name' => 'Prestige Table Works Co.', 'slug' => 'prestige-table-works']
        );

        $this->user = User::where('email', 'admin@example.com')->firstOrFail();
        Auth::login($this->user);
    }

    private function createLocations(): void
    {
        $this->command->info('Creating Locations...');

        $this->locRM = \App\Models\Location::firstOrCreate(
            ['code' => 'LOC-RM'],
            ['name' => 'Raw Material Store', 'type' => 'warehouse', 'organization_id' => $this->orgId]
        );
        $this->locProd = \App\Models\Location::firstOrCreate(
            ['code' => 'LOC-PROD'],
            ['name' => 'Production Floor', 'type' => 'production', 'organization_id' => $this->orgId]
        );
        $this->locQA = \App\Models\Location::firstOrCreate(
            ['code' => 'LOC-QA'],
            ['name' => 'Quality Inspection Area', 'type' => 'internal', 'organization_id' => $this->orgId]
        );
        $this->locFG = \App\Models\Location::firstOrCreate(
            ['code' => 'LOC-FG'],
            ['name' => 'Finished Goods Warehouse', 'type' => 'warehouse', 'organization_id' => $this->orgId]
        );
        $this->locScrap = \App\Models\Location::firstOrCreate(
            ['code' => 'LOC-SCRAP'],
            ['name' => 'Scrap Yard', 'type' => 'scrap', 'organization_id' => $this->orgId]
        );
    }

    private function createWorkCenters(): void
    {
        $this->command->info('Creating Work Centers...');

        $this->wcCut = WorkCenter::firstOrCreate(
            ['code' => 'WC-CUT'],
            ['name' => 'Cutting & Sawing', 'cost_per_hour' => 35.00, 'organization_id' => $this->orgId]
        );
        $this->wcCNC = WorkCenter::firstOrCreate(
            ['code' => 'WC-CNC'],
            ['name' => 'CNC Machining', 'cost_per_hour' => 60.00, 'organization_id' => $this->orgId]
        );
        $this->wcAssm = WorkCenter::firstOrCreate(
            ['code' => 'WC-ASSM'],
            ['name' => 'Assembly Station', 'cost_per_hour' => 25.00, 'organization_id' => $this->orgId]
        );
        $this->wcSand = WorkCenter::firstOrCreate(
            ['code' => 'WC-SAND'],
            ['name' => 'Sanding & Finishing', 'cost_per_hour' => 20.00, 'organization_id' => $this->orgId]
        );
        $this->wcQA = WorkCenter::firstOrCreate(
            ['code' => 'WC-QA'],
            ['name' => 'Quality Inspection', 'cost_per_hour' => 18.00, 'organization_id' => $this->orgId]
        );
    }

    private function createRawMaterials(): void
    {
        $this->command->info('Creating Raw Materials...');

        $this->oakBoard = Product::create([
            'code'            => 'RM-OAK-BOARD',
            'name'            => 'Oak Wood Board 2400x600mm',
            'type'            => 'raw',
            'uom'             => 'pcs',
            'cost'            => 45.00,
            'tracking'        => 'lot',
            'description'     => 'Solid oak board, 45mm thickness, kiln-dried',
            'organization_id' => $this->orgId,
        ]);

        $this->pinepanel = Product::create([
            'code'            => 'RM-PINE-PANEL',
            'name'            => 'Pine Wood Panel 1800x400mm',
            'type'            => 'raw',
            'uom'             => 'pcs',
            'cost'            => 22.00,
            'tracking'        => 'lot',
            'description'     => 'Finger-jointed pine panel, 18mm thickness',
            'organization_id' => $this->orgId,
        ]);

        $this->steelTube = Product::create([
            'code'            => 'RM-STEEL-TUBE',
            'name'            => 'Steel Square Tube 40x40mm',
            'type'            => 'raw',
            'uom'             => 'm',
            'cost'            => 8.50,
            'tracking'        => 'lot',
            'description'     => 'Cold-rolled steel square tube, 2mm wall thickness',
            'organization_id' => $this->orgId,
        ]);

        $this->mdfBoard = Product::create([
            'code'            => 'RM-MDF-18',
            'name'            => 'MDF Board 2400x1200mm 18mm',
            'type'            => 'raw',
            'uom'             => 'pcs',
            'cost'            => 18.00,
            'tracking'        => 'lot',
            'description'     => 'Medium-density fibreboard, moisture resistant grade',
            'organization_id' => $this->orgId,
        ]);

        $this->screwM6 = Product::create([
            'code'            => 'RM-SCREW-M6',
            'name'            => 'Wood Screw M6x50mm',
            'type'            => 'raw',
            'uom'             => 'pcs',
            'cost'            => 0.05,
            'tracking'        => 'none',
            'description'     => 'Zinc-plated countersunk wood screws',
            'organization_id' => $this->orgId,
        ]);

        $this->cornerBracket = Product::create([
            'code'            => 'RM-BRACKET',
            'name'            => 'Metal Corner Bracket 50x50mm',
            'type'            => 'raw',
            'uom'             => 'pcs',
            'cost'            => 0.80,
            'tracking'        => 'none',
            'description'     => 'Galvanised steel corner bracket',
            'organization_id' => $this->orgId,
        ]);

        $this->woodVarnish = Product::create([
            'code'            => 'RM-VARNISH',
            'name'            => 'Wood Varnish Clear Satin 1L',
            'type'            => 'raw',
            'uom'             => 'L',
            'cost'            => 12.00,
            'tracking'        => 'lot',
            'description'     => 'Water-based clear satin varnish, UV resistant',
            'organization_id' => $this->orgId,
        ]);

        $this->sandpaper = Product::create([
            'code'            => 'RM-SAND-120',
            'name'            => 'Sandpaper Sheet 120 Grit',
            'type'            => 'raw',
            'uom'             => 'pcs',
            'cost'            => 0.30,
            'tracking'        => 'none',
            'description'     => 'Aluminium oxide abrasive sheet 230x280mm',
            'organization_id' => $this->orgId,
        ]);
    }

    private function createFinishedGoods(): void
    {
        $this->command->info('Creating Finished Goods...');

        $this->diningTable = Product::create([
            'code'            => 'FG-DINING-OAK-6S',
            'name'            => 'Dining Table 6-Seater (Oak)',
            'type'            => 'finished',
            'uom'             => 'pcs',
            'cost'            => 0,
            'tracking'        => 'serial',
            'description'     => 'Solid oak dining table, seats 6, steel hairpin legs, satin finish',
            'organization_id' => $this->orgId,
        ]);

        $this->coffeeTable = Product::create([
            'code'            => 'FG-COFFEE-PINE',
            'name'            => 'Coffee Table (Pine)',
            'type'            => 'finished',
            'uom'             => 'pcs',
            'cost'            => 0,
            'tracking'        => 'lot',
            'description'     => 'Rustic pine coffee table with lower shelf, steel frame',
            'organization_id' => $this->orgId,
        ]);

        $this->officeDesk = Product::create([
            'code'            => 'FG-DESK-LSHAPE',
            'name'            => 'L-Shape Office Desk (MDF)',
            'type'            => 'finished',
            'uom'             => 'pcs',
            'cost'            => 0,
            'tracking'        => 'serial',
            'description'     => 'Corner office desk, 1800x1200mm L-shape, white MDF top, steel legs',
            'organization_id' => $this->orgId,
        ]);

        $this->patioTable = Product::create([
            'code'            => 'FG-PATIO-OUT',
            'name'            => 'Outdoor Patio Table (Pine)',
            'type'            => 'finished',
            'uom'             => 'pcs',
            'cost'            => 0,
            'tracking'        => 'lot',
            'description'     => 'Weather-treated pine patio table, galvanised steel frame',
            'organization_id' => $this->orgId,
        ]);
    }

    private function seedInitialStock(): void
    {
        $this->command->info('Seeding Initial Stock...');

        // Oak boards — two lots from different timber suppliers
        $this->addStock($this->oakBoard, $this->locRM, 200, 'LOT-OAK-2026-001', now()->subDays(60), now()->addDays(365));
        $this->addStock($this->oakBoard, $this->locRM, 150, 'LOT-OAK-2026-002', now()->subDays(20), now()->addDays(400));

        // Pine panels — one lot
        $this->addStock($this->pinepanel, $this->locRM, 300, 'LOT-PINE-2026-001', now()->subDays(45), now()->addDays(365));

        // Steel tubes — two lots
        $this->addStock($this->steelTube, $this->locRM, 500, 'LOT-STEEL-2026-001', now()->subDays(90));
        $this->addStock($this->steelTube, $this->locRM, 250, 'LOT-STEEL-2026-002', now()->subDays(15));

        // MDF boards
        $this->addStock($this->mdfBoard, $this->locRM, 180, 'LOT-MDF-2026-001', now()->subDays(30));

        // Consumables (no lot tracking)
        $this->addStock($this->screwM6, $this->locRM, 50000);
        $this->addStock($this->cornerBracket, $this->locRM, 8000);
        $this->addStock($this->sandpaper, $this->locRM, 2000);

        // Varnish — one lot, with expiry
        $this->addStock($this->woodVarnish, $this->locRM, 400, 'LOT-VARN-2026-001', now()->subDays(10), now()->addMonths(18));
    }

    private function createStockAdjustments(): void
    {
        $this->command->info('Creating Stock Adjustments...');

        // Physical inventory count — slight correction on oak boards
        \App\Models\StockAdjustment::create([
            'organization_id' => $this->orgId,
            'product_id'      => $this->oakBoard->id,
            'location_id'     => $this->locRM->id,
            'lot_id'          => \App\Models\Lot::where('name', 'LOT-OAK-2026-001')->value('id'),
            'quantity'        => -3,
            'reason'          => 'physical_count',
            'reference'       => 'PHYS-COUNT-2026-01',
            'notes'           => 'Quarterly physical count — 3 boards found damaged and reclassified to scrap',
            'user_id'         => $this->user->id,
            'created_at'      => now()->subDays(14),
        ]);

        // Purchase receipt — top up screws
        \App\Models\StockAdjustment::create([
            'organization_id' => $this->orgId,
            'product_id'      => $this->screwM6->id,
            'location_id'     => $this->locRM->id,
            'quantity'        => 20000,
            'reason'          => 'purchase',
            'reference'       => 'PO-2026-0044',
            'notes'           => 'Restocking order from FastFix Hardware',
            'user_id'         => $this->user->id,
            'created_at'      => now()->subDays(7),
        ]);

        // Damage loss — water-damaged varnish
        \App\Models\StockAdjustment::create([
            'organization_id' => $this->orgId,
            'product_id'      => $this->woodVarnish->id,
            'location_id'     => $this->locRM->id,
            'lot_id'          => \App\Models\Lot::where('name', 'LOT-VARN-2026-001')->value('id'),
            'quantity'        => -8,
            'reason'          => 'damage',
            'reference'       => null,
            'notes'           => 'Roof leak in store room — 8 litres contaminated',
            'user_id'         => $this->user->id,
            'created_at'      => now()->subDays(5),
        ]);
    }

    private function createBomsAndOperations(): void
    {
        $this->command->info('Creating BOMs & Operations...');

        // ── BOM 1: Dining Table 6-Seater (Oak) ──────────────────────────────
        $this->bomDining = Bom::create([
            'product_id'      => $this->diningTable->id,
            'organization_id' => $this->orgId,
            'qty_produced'    => 1,
            'type'            => 'normal',
            'is_active'       => true,
        ]);

        $this->bomDining->lines()->createMany([
            ['product_id' => $this->oakBoard->id,      'quantity' => 6,  'sequence' => 10],
            ['product_id' => $this->steelTube->id,     'quantity' => 4,  'sequence' => 20],
            ['product_id' => $this->cornerBracket->id, 'quantity' => 12, 'sequence' => 30],
            ['product_id' => $this->screwM6->id,       'quantity' => 24, 'sequence' => 40],
            ['product_id' => $this->woodVarnish->id,   'quantity' => 2,  'sequence' => 50],
            ['product_id' => $this->sandpaper->id,     'quantity' => 6,  'sequence' => 60],
        ]);

        $this->bomDining->operations()->createMany([
            ['sequence' => 10, 'name' => 'Cut Oak Boards',         'work_center_id' => $this->wcCNC->id,  'duration_minutes' => 30, 'needs_quality_check' => false],
            ['sequence' => 20, 'name' => 'Shape & Profile Legs',   'work_center_id' => $this->wcCNC->id,  'duration_minutes' => 45, 'needs_quality_check' => true],
            ['sequence' => 30, 'name' => 'Assemble Table Frame',   'work_center_id' => $this->wcAssm->id, 'duration_minutes' => 60, 'needs_quality_check' => false],
            ['sequence' => 40, 'name' => 'Sand & Apply Varnish',   'work_center_id' => $this->wcSand->id, 'duration_minutes' => 40, 'needs_quality_check' => true],
            ['sequence' => 50, 'name' => 'Final QA Inspection',    'work_center_id' => $this->wcQA->id,   'duration_minutes' => 20, 'needs_quality_check' => true],
        ]);

        // ── BOM 2: Coffee Table (Pine) ───────────────────────────────────────
        $this->bomCoffee = Bom::create([
            'product_id'      => $this->coffeeTable->id,
            'organization_id' => $this->orgId,
            'qty_produced'    => 1,
            'type'            => 'normal',
            'is_active'       => true,
        ]);

        $this->bomCoffee->lines()->createMany([
            ['product_id' => $this->pinepanel->id,     'quantity' => 3,  'sequence' => 10],
            ['product_id' => $this->steelTube->id,     'quantity' => 2,  'sequence' => 20],
            ['product_id' => $this->screwM6->id,       'quantity' => 16, 'sequence' => 30],
            ['product_id' => $this->woodVarnish->id,   'quantity' => 1,  'sequence' => 40],
            ['product_id' => $this->sandpaper->id,     'quantity' => 3,  'sequence' => 50],
        ]);

        $this->bomCoffee->operations()->createMany([
            ['sequence' => 10, 'name' => 'Cut Pine Panels',       'work_center_id' => $this->wcCut->id,  'duration_minutes' => 20, 'needs_quality_check' => false],
            ['sequence' => 20, 'name' => 'Assemble Table',        'work_center_id' => $this->wcAssm->id, 'duration_minutes' => 35, 'needs_quality_check' => false],
            ['sequence' => 30, 'name' => 'Finish & Varnish',      'work_center_id' => $this->wcSand->id, 'duration_minutes' => 25, 'needs_quality_check' => true],
        ]);

        // ── BOM 3: L-Shape Office Desk (MDF) ────────────────────────────────
        $this->bomDesk = Bom::create([
            'product_id'      => $this->officeDesk->id,
            'organization_id' => $this->orgId,
            'qty_produced'    => 1,
            'type'            => 'normal',
            'is_active'       => true,
        ]);

        $this->bomDesk->lines()->createMany([
            ['product_id' => $this->mdfBoard->id,      'quantity' => 4,  'sequence' => 10],
            ['product_id' => $this->steelTube->id,     'quantity' => 6,  'sequence' => 20],
            ['product_id' => $this->cornerBracket->id, 'quantity' => 8,  'sequence' => 30],
            ['product_id' => $this->screwM6->id,       'quantity' => 20, 'sequence' => 40],
            ['product_id' => $this->woodVarnish->id,   'quantity' => 1,  'sequence' => 50],
            ['product_id' => $this->sandpaper->id,     'quantity' => 4,  'sequence' => 60],
        ]);

        $this->bomDesk->operations()->createMany([
            ['sequence' => 10, 'name' => 'CNC Cut MDF Panels',     'work_center_id' => $this->wcCNC->id,  'duration_minutes' => 35, 'needs_quality_check' => false],
            ['sequence' => 20, 'name' => 'Weld Steel Frame',       'work_center_id' => $this->wcAssm->id, 'duration_minutes' => 50, 'needs_quality_check' => true],
            ['sequence' => 30, 'name' => 'Assemble Desk Top',      'work_center_id' => $this->wcAssm->id, 'duration_minutes' => 40, 'needs_quality_check' => false],
            ['sequence' => 40, 'name' => 'Sand, Prime & Paint',    'work_center_id' => $this->wcSand->id, 'duration_minutes' => 30, 'needs_quality_check' => false],
            ['sequence' => 50, 'name' => 'Final Inspection',       'work_center_id' => $this->wcQA->id,   'duration_minutes' => 15, 'needs_quality_check' => true],
        ]);
    }

    private function createEquipmentAndMaintenance(): void
    {
        $this->command->info('Creating Equipment & Maintenance...');

        // ── Equipment ────────────────────────────────────────────────────────
        $cncRouter = \App\Models\Equipment::create([
            'name'                     => 'CNC Router XR-500',
            'code'                     => 'EQ-CNC-01',
            'work_center_id'           => $this->wcCNC->id,
            'status'                   => 'operational',
            'organization_id'          => $this->orgId,
            'maintenance_interval_days'=> 60,
            'last_maintenance'         => now()->subDays(25),
            'next_maintenance'         => now()->addDays(35),
            'notes'                    => 'X-Carve Pro series — 3 spindle heads',
        ]);

        $bandSaw = \App\Models\Equipment::create([
            'name'                     => 'Industrial Band Saw BS-200',
            'code'                     => 'EQ-SAW-01',
            'work_center_id'           => $this->wcCut->id,
            'status'                   => 'maintenance',
            'organization_id'          => $this->orgId,
            'maintenance_interval_days'=> 30,
            'last_maintenance'         => now()->subDays(38),
            'next_maintenance'         => now()->subDays(8),
            'notes'                    => 'Overdue for blade replacement — currently locked out',
        ]);

        $sprayBooth = \App\Models\Equipment::create([
            'name'                     => 'Spray Booth SP-100',
            'code'                     => 'EQ-SPRAY-01',
            'work_center_id'           => $this->wcSand->id,
            'status'                   => 'operational',
            'organization_id'          => $this->orgId,
            'maintenance_interval_days'=> 90,
            'last_maintenance'         => now()->subDays(20),
            'next_maintenance'         => now()->addDays(70),
            'notes'                    => 'HVLP spray system with downdraft ventilation',
        ]);

        $hydraulicPress = \App\Models\Equipment::create([
            'name'                     => 'Hydraulic Press HP-300',
            'code'                     => 'EQ-PRESS-01',
            'work_center_id'           => $this->wcAssm->id,
            'status'                   => 'broken',
            'organization_id'          => $this->orgId,
            'maintenance_interval_days'=> 180,
            'last_maintenance'         => now()->subDays(200),
            'next_maintenance'         => now()->subDays(20),
            'notes'                    => 'Hydraulic seal failure — awaiting parts from supplier',
        ]);

        // ── Maintenance Schedules ────────────────────────────────────────────
        \App\Models\MaintenanceSchedule::create([
            'organization_id' => $this->orgId,
            'equipment_id'    => $cncRouter->id,
            'name'            => 'CNC Spindle Calibration',
            'trigger_type'    => 'time',
            'interval_days'   => 60,
            'last_maintenance' => now()->subDays(25),
            'next_maintenance' => now()->addDays(35),
            'instructions'    => "1. Power off and lock out.\n2. Check spindle runout with dial indicator (max 0.02mm).\n3. Re-calibrate X/Y/Z zero points.\n4. Run calibration part program.\n5. Inspect collets and replace if worn.",
            'is_active'       => true,
        ]);

        \App\Models\MaintenanceSchedule::create([
            'organization_id' => $this->orgId,
            'equipment_id'    => $bandSaw->id,
            'name'            => 'Monthly Blade Inspection',
            'trigger_type'    => 'time',
            'interval_days'   => 30,
            'last_maintenance' => now()->subDays(38),
            'next_maintenance' => now()->subDays(8),
            'instructions'    => "1. Inspect blade for cracks, missing teeth, or excessive wear.\n2. Check blade tension and tracking.\n3. Lubricate guides and bearings.\n4. Verify blade guard alignment.",
            'is_active'       => true,
        ]);

        \App\Models\MaintenanceSchedule::create([
            'organization_id'  => $this->orgId,
            'equipment_id'     => $sprayBooth->id,
            'name'             => 'Filter Replacement & Fan Check',
            'trigger_type'     => 'time',
            'interval_days'    => 90,
            'last_maintenance'  => now()->subDays(20),
            'next_maintenance'  => now()->addDays(70),
            'instructions'     => "1. Replace intake and exhaust filters.\n2. Clean fan blades and housing.\n3. Verify airflow velocity (min 0.5 m/s).\n4. Check explosion-proof lighting.",
            'is_active'        => true,
        ]);

        \App\Models\MaintenanceSchedule::create([
            'organization_id'  => $this->orgId,
            'equipment_id'     => $hydraulicPress->id,
            'name'             => 'Bi-Annual Hydraulic Service',
            'trigger_type'     => 'time',
            'interval_days'    => 180,
            'last_maintenance'  => now()->subDays(200),
            'next_maintenance'  => now()->subDays(20),
            'instructions'     => "1. Change hydraulic fluid and filter.\n2. Inspect all seals and hoses.\n3. Check pressure relief valve setting.\n4. Test emergency stop and safety interlocks.",
            'is_active'        => true,
        ]);

        // ── Maintenance Requests ─────────────────────────────────────────────

        // Corrective — Band Saw blade failure (in progress)
        \App\Models\MaintenanceRequest::create([
            'name'            => 'MR-2026-00001',
            'equipment_id'    => $bandSaw->id,
            'request_type'    => 'corrective',
            'priority'        => 'high',
            'status'          => 'in_progress',
            'description'     => 'Blade snapped mid-cut causing equipment shutdown. Production on WC-CUT is halted.',
            'diagnosis'       => 'Fatigue fracture on blade weld seam. Blade has exceeded service life by ~15%.',
            'actions_taken'   => 'Machine locked out. Replacement blade on order (PO-2026-0089). ETA 2 days.',
            'requested_by'    => $this->user->id,
            'scheduled_date'  => now()->subDays(2),
            'actual_start'    => now()->subDays(2),
            'parts_cost'      => 120.00,
            'labor_cost'      => 0.00,
            'organization_id' => $this->orgId,
        ]);

        // Preventive — CNC Router scheduled service (confirmed)
        \App\Models\MaintenanceRequest::create([
            'name'            => 'MR-2026-00002',
            'equipment_id'    => $cncRouter->id,
            'request_type'    => 'preventive',
            'priority'        => 'normal',
            'status'          => 'confirmed',
            'description'     => 'Scheduled 60-day spindle calibration and lubrication service.',
            'requested_by'    => $this->user->id,
            'scheduled_date'  => now()->addDays(35),
            'parts_cost'      => 0.00,
            'labor_cost'      => 0.00,
            'organization_id' => $this->orgId,
        ]);

        // Corrective — Hydraulic Press seal failure (done)
        $mrDone = \App\Models\MaintenanceRequest::create([
            'name'            => 'MR-2026-00003',
            'equipment_id'    => $hydraulicPress->id,
            'request_type'    => 'corrective',
            'priority'        => 'critical',
            'status'          => 'done',
            'description'     => 'Hydraulic fluid leak detected at main cylinder seal. Press fails to hold pressure above 150 bar.',
            'diagnosis'       => 'Primary piston seal deteriorated — likely due to contaminated hydraulic fluid.',
            'actions_taken'   => 'Replaced primary and secondary seals. Flushed hydraulic system and refilled with ISO VG 46. Pressure tested to 300 bar — holding.',
            'requested_by'    => $this->user->id,
            'scheduled_date'  => now()->subDays(20),
            'actual_start'    => now()->subDays(19),
            'actual_end'      => now()->subDays(18),
            'duration_hours'  => 8.5,
            'parts_cost'      => 380.00,
            'labor_cost'      => 212.50,
            'organization_id' => $this->orgId,
        ]);

        // ── Maintenance Log (for the completed request) ──────────────────────
        \App\Models\MaintenanceLog::create([
            'organization_id' => $this->orgId,
            'equipment_id'    => $hydraulicPress->id,
            'type'            => 'corrective',
            'description'     => 'Emergency seal replacement — Hydraulic Press HP-300',
            'actions_taken'   => 'Replaced primary and secondary cylinder seals. Full hydraulic fluid flush with ISO VG 46. Pressure test passed at 300 bar. Machine returned to service.',
            'cost'            => 592.50,
            'performed_by'    => $this->user->id,
            'performed_at'    => now()->subDays(18),
        ]);

        \App\Models\MaintenanceLog::create([
            'organization_id' => $this->orgId,
            'equipment_id'    => $cncRouter->id,
            'type'            => 'preventive',
            'description'     => 'Routine spindle calibration and lubrication — EQ-CNC-01',
            'actions_taken'   => 'Calibrated X/Y/Z zero points. Replaced collet #2 (worn). Lubricated all linear rails. Ran calibration part — pass.',
            'cost'            => 45.00,
            'performed_by'    => $this->user->id,
            'performed_at'    => now()->subDays(25),
        ]);
    }

    private function createManufacturingOrders(): void
    {
        $this->command->info('Creating Manufacturing Orders (all statuses)...');

        $moService  = app(ManufacturingOrderService::class);
        $oeeService = app(OeeService::class);

        $scenarios = [
            // [bom, product, qty, priority, status, days_offset]
            ['bom' => $this->bomCoffee,  'product' => $this->coffeeTable,  'qty' => 5,  'priority' => 'normal', 'status' => 'draft',      'offset' => 5],
            ['bom' => $this->bomDesk,    'product' => $this->officeDesk,   'qty' => 3,  'priority' => 'high',   'status' => 'draft',      'offset' => 7],
            ['bom' => $this->bomDining,  'product' => $this->diningTable,  'qty' => 4,  'priority' => 'normal', 'status' => 'confirmed',  'offset' => 3],
            ['bom' => $this->bomCoffee,  'product' => $this->coffeeTable,  'qty' => 10, 'priority' => 'high',   'status' => 'confirmed',  'offset' => 4],
            ['bom' => $this->bomDesk,    'product' => $this->officeDesk,   'qty' => 2,  'priority' => 'urgent', 'status' => 'scheduled',  'offset' => 2],
            ['bom' => $this->bomDining,  'product' => $this->diningTable,  'qty' => 6,  'priority' => 'normal', 'status' => 'scheduled',  'offset' => 6],
            ['bom' => $this->bomCoffee,  'product' => $this->coffeeTable,  'qty' => 8,  'priority' => 'high',   'status' => 'in_progress','offset' => 0],
            ['bom' => $this->bomDining,  'product' => $this->diningTable,  'qty' => 2,  'priority' => 'urgent', 'status' => 'in_progress','offset' => 0],
            ['bom' => $this->bomDesk,    'product' => $this->officeDesk,   'qty' => 5,  'priority' => 'normal', 'status' => 'done',       'offset' => -5],
            ['bom' => $this->bomCoffee,  'product' => $this->coffeeTable,  'qty' => 12, 'priority' => 'normal', 'status' => 'done',       'offset' => -3],
            ['bom' => $this->bomDining,  'product' => $this->diningTable,  'qty' => 3,  'priority' => 'high',   'status' => 'done',       'offset' => -8],
            ['bom' => $this->bomDesk,    'product' => $this->officeDesk,   'qty' => 2,  'priority' => 'normal', 'status' => 'done',       'offset' => -10],
        ];

        foreach ($scenarios as $s) {
            $mo = $moService->create([
                'organization_id' => $this->orgId,
                'product_id'      => $s['product']->id,
                'bom_id'          => $s['bom']->id,
                'qty_to_produce'  => $s['qty'],
                'priority'        => $s['priority'],
                'scheduled_start' => now()->addDays($s['offset']),
                'scheduled_end'   => now()->addDays($s['offset'] + 2),
                'notes'           => "UAT order — {$s['product']->name}",
            ]);

            if ($s['status'] === 'draft') {
                continue;
            }

            $moService->confirm($mo);

            if ($s['status'] === 'confirmed') {
                continue;
            }

            if ($s['status'] === 'scheduled') {
                $moService->schedule($mo, [
                    'scheduled_start' => now()->addDays($s['offset']),
                    'scheduled_end'   => now()->addDays($s['offset'] + 2),
                ]);
                continue;
            }

            $moService->start($mo);

            if ($s['status'] === 'in_progress') {
                // Advance first work order to in_progress with partial production
                $firstWo = \App\Models\WorkOrder::where('manufacturing_order_id', $mo->id)
                    ->orderBy('sequence')->first();
                if ($firstWo) {
                    $firstWo->update([
                        'status'     => 'in_progress',
                        'started_at' => now()->subHours(3),
                        'actual_start' => now()->subHours(3),
                        'assigned_to' => $this->user->id,
                    ]);
                }
                continue;
            }

            // status === 'done': complete all work orders then close MO
            $mo->refresh();
            foreach ($mo->workOrders()->orderBy('sequence')->get() as $wo) {
                $variance      = rand(88, 112) / 100;
                $durationActual = round($wo->duration_expected * $variance, 2);

                $wo->update([
                    'status'            => 'done',
                    'started_at'        => now()->subDays(abs($s['offset']))->subHours(6),
                    'actual_start'      => now()->subDays(abs($s['offset']))->subHours(6),
                    'finished_at'       => now()->subDays(abs($s['offset']))->subHours(6)->addMinutes($durationActual),
                    'duration_actual'   => $durationActual,
                    'quantity_produced' => $mo->qty_to_produce,
                    'assigned_to'       => $this->user->id,
                    'notes'             => 'Completed within expected time',
                ]);

                if ($wo->operation->needs_quality_check) {
                    $pass = rand(0, 4) > 0; // 80% pass rate
                    $wo->update([
                        'qa_status'   => $pass ? 'pass' : 'fail',
                        'qa_comments' => $pass
                            ? 'All dimensions within tolerance. Surface finish acceptable.'
                            : 'Minor surface defect on corner joint — rework required.',
                        'qa_by'       => $this->user->id,
                        'qa_at'       => now()->subDays(abs($s['offset']))->subHours(4),
                    ]);
                }

                // OEE calculation
                $oeeService->calculate($wo);

                // Labor cost entry
                if ($wo->workCenter && $wo->workCenter->cost_per_hour > 0) {
                    $laborHours = $durationActual / 60;
                    \App\Models\CostEntry::create([
                        'organization_id'        => $this->orgId,
                        'manufacturing_order_id' => $mo->id,
                        'work_order_id'          => $wo->id,
                        'product_id'             => $mo->product_id,
                        'cost_type'              => 'labor',
                        'quantity'               => $laborHours,
                        'unit_cost'              => $wo->workCenter->cost_per_hour,
                        'total_cost'             => round($laborHours * $wo->workCenter->cost_per_hour, 2),
                        'notes'                  => 'Labor: ' . $wo->operation->name,
                        'created_at'             => now()->subDays(abs($s['offset'])),
                    ]);
                }
            }

            // Overhead cost entry per completed MO
            \App\Models\CostEntry::create([
                'organization_id'        => $this->orgId,
                'manufacturing_order_id' => $mo->id,
                'product_id'             => $mo->product_id,
                'cost_type'              => 'overhead',
                'quantity'               => $mo->qty_to_produce,
                'unit_cost'              => 15.00,
                'total_cost'             => $mo->qty_to_produce * 15.00,
                'notes'                  => 'Factory overhead allocation',
                'created_at'             => now()->subDays(abs($s['offset'])),
            ]);

            $moService->complete($mo, [
                'qty_produced' => $mo->qty_to_produce,
                'location_id'  => $this->locFG->id,
            ]);
        }
    }

    private function createScrapRecords(): void
    {
        $this->command->info('Creating Scrap Records...');

        // Damaged oak boards found during incoming inspection
        \App\Models\Scrap::create([
            'organization_id' => $this->orgId,
            'product_id'      => $this->oakBoard->id,
            'lot_id'          => \App\Models\Lot::where('name', 'LOT-OAK-2026-001')->value('id'),
            'location_id'     => $this->locRM->id,
            'quantity'        => 3,
            'reason'          => 'Warped boards rejected during incoming QA inspection — humidity damage in transit',
            'reported_by'     => $this->user->id,
            'created_at'      => now()->subDays(14),
        ]);

        // Scrap from a completed MO (material waste during cutting)
        $doneMo = ManufacturingOrder::where('status', 'done')->first();
        if ($doneMo) {
            \App\Models\Scrap::create([
                'organization_id'        => $this->orgId,
                'manufacturing_order_id' => $doneMo->id,
                'product_id'             => $this->oakBoard->id,
                'location_id'            => $this->locScrap->id,
                'quantity'               => 1,
                'reason'                 => 'Off-cut waste from CNC profile operation — unusable strip',
                'reported_by'            => $this->user->id,
                'created_at'             => now()->subDays(5),
            ]);

            // Material variance cost for the same MO
            \App\Models\CostEntry::create([
                'organization_id'        => $this->orgId,
                'manufacturing_order_id' => $doneMo->id,
                'product_id'             => $this->oakBoard->id,
                'cost_type'              => 'material_variance',
                'quantity'               => 1,
                'unit_cost'              => 45.00,
                'total_cost'             => 45.00,
                'notes'                  => 'Oak board off-cut scrap loss — CNC profiling',
                'created_at'             => now()->subDays(5),
            ]);
        }

        // Scrap from finishing — varnish defect on coffee table batch
        $coffeeMo = ManufacturingOrder::where('status', 'done')
            ->where('product_id', $this->coffeeTable->id)
            ->first();
        if ($coffeeMo) {
            $scrap = \App\Models\Scrap::create([
                'organization_id'        => $this->orgId,
                'manufacturing_order_id' => $coffeeMo->id,
                'product_id'             => $this->woodVarnish->id,
                'lot_id'                 => \App\Models\Lot::where('name', 'LOT-VARN-2026-001')->value('id'),
                'location_id'            => $this->locScrap->id,
                'quantity'               => 2,
                'reason'                 => 'Varnish applied at wrong temperature — blistering on 2 units, stripped and redone',
                'reported_by'            => $this->user->id,
                'created_at'             => now()->subDays(3),
            ]);

            \App\Models\CostEntry::create([
                'organization_id'        => $this->orgId,
                'manufacturing_order_id' => $coffeeMo->id,
                'scrap_id'               => $scrap->id,
                'product_id'             => $this->woodVarnish->id,
                'cost_type'              => 'scrap',
                'quantity'               => 2,
                'unit_cost'              => 12.00,
                'total_cost'             => 24.00,
                'notes'                  => 'Varnish scrap — temperature deviation during spray application',
                'created_at'             => now()->subDays(3),
            ]);
        }
    }

    private function createUnbuildOrder(): void
    {
        $this->command->info('Creating Unbuild Order...');

        // Find a completed dining table MO to unbuild from
        $sourceMo = ManufacturingOrder::where('status', 'done')
            ->where('product_id', $this->diningTable->id)
            ->first();

        \App\Models\UnbuildOrder::create([
            'organization_id'        => $this->orgId,
            'name'                   => 'UB-2026-001',
            'product_id'             => $this->diningTable->id,
            'bom_id'                 => $this->bomDining->id,
            'quantity'               => 1,
            'status'                 => 'done',
            'manufacturing_order_id' => $sourceMo?->id,
            'location_id'            => $this->locFG->id,
            'component_location_id'  => $this->locRM->id,
            'reason'                 => 'Customer return — tabletop delamination fault. Unit disassembled; steel frame and hardware recovered to stock. Oak boards written off.',
            'created_by'             => $this->user->id,
            'created_at'             => now()->subDays(2),
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function addStock(
        Product $product,
        \App\Models\Location $location,
        float $qty,
        ?string $lotName = null,
        ?\Carbon\Carbon $manufacturedDate = null,
        ?\Carbon\Carbon $expiryDate = null
    ): void {
        $lotId = null;

        if ($lotName) {
            $lot = \App\Models\Lot::firstOrCreate(
                ['name' => $lotName],
                [
                    'product_id'        => $product->id,
                    'organization_id'   => $this->orgId,
                    'manufactured_date' => $manufacturedDate ?? now(),
                    'expiry_date'       => $expiryDate,
                    'initial_qty'       => $qty,
                ]
            );
            $lotId = $lot->id;
        }

        \App\Models\Stock::create([
            'organization_id' => $this->orgId,
            'product_id'      => $product->id,
            'location_id'     => $location->id,
            'quantity'        => $qty,
            'lot_id'          => $lotId,
        ]);
    }
}
