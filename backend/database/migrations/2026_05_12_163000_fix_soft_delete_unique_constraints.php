<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // work_centers.code
        DB::statement('ALTER TABLE work_centers DROP CONSTRAINT IF EXISTS work_centers_code_unique');
        DB::statement('CREATE UNIQUE INDEX work_centers_code_unique_active ON work_centers (code) WHERE deleted_at IS NULL');

        // locations.code
        DB::statement('ALTER TABLE locations DROP CONSTRAINT IF EXISTS locations_code_unique');
        DB::statement('CREATE UNIQUE INDEX locations_code_unique_active ON locations (code) WHERE deleted_at IS NULL');

        // equipment.code
        DB::statement('ALTER TABLE equipment DROP CONSTRAINT IF EXISTS equipment_code_unique');
        DB::statement('CREATE UNIQUE INDEX equipment_code_unique_active ON equipment (code) WHERE deleted_at IS NULL');

        // manufacturing_orders.name
        DB::statement('ALTER TABLE manufacturing_orders DROP CONSTRAINT IF EXISTS manufacturing_orders_name_unique');
        DB::statement('CREATE UNIQUE INDEX manufacturing_orders_name_unique_active ON manufacturing_orders (name) WHERE deleted_at IS NULL');

        // maintenance_requests.name
        DB::statement('ALTER TABLE maintenance_requests DROP CONSTRAINT IF EXISTS maintenance_requests_name_unique');
        DB::statement('CREATE UNIQUE INDEX maintenance_requests_name_unique_active ON maintenance_requests (name) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS work_centers_code_unique_active');
        DB::statement('ALTER TABLE work_centers ADD CONSTRAINT work_centers_code_unique UNIQUE (code)');

        DB::statement('DROP INDEX IF EXISTS locations_code_unique_active');
        DB::statement('ALTER TABLE locations ADD CONSTRAINT locations_code_unique UNIQUE (code)');

        DB::statement('DROP INDEX IF EXISTS equipment_code_unique_active');
        DB::statement('ALTER TABLE equipment ADD CONSTRAINT equipment_code_unique UNIQUE (code)');

        DB::statement('DROP INDEX IF EXISTS manufacturing_orders_name_unique_active');
        DB::statement('ALTER TABLE manufacturing_orders ADD CONSTRAINT manufacturing_orders_name_unique UNIQUE (name)');

        DB::statement('DROP INDEX IF EXISTS maintenance_requests_name_unique_active');
        DB::statement('ALTER TABLE maintenance_requests ADD CONSTRAINT maintenance_requests_name_unique UNIQUE (name)');
    }
};
