<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, the enum is typically implemented as a check constraint.
        // We drop the existing check constraint and add a new one with the expanded reasons.
        // Skip for SQLite as it doesn't support DROP CONSTRAINT IF EXISTS.
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE stock_adjustments DROP CONSTRAINT IF EXISTS stock_adjustments_reason_check");
            
            $reasons = [
                'physical_count', 
                'purchase', 
                'correction', 
                'loss', 
                'damage', 
                'initial', 
                'manufacturing_consumption', 
                'manufacturing_production',
                'mo_cancelled',
                'manufacturing_consumption_update',
                'consumption_reversal'
            ];
            
            $reasonList = "'" . implode("', '", $reasons) . "'";
            
            DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT stock_adjustments_reason_check CHECK (reason IN ($reasonList))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Skip for SQLite as it doesn't support these operations
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE stock_adjustments DROP CONSTRAINT IF EXISTS stock_adjustments_reason_check");
            
            $reasons = [
                'physical_count', 
                'purchase', 
                'correction', 
                'loss', 
                'damage', 
                'initial', 
                'manufacturing_consumption', 
                'manufacturing_production'
            ];
            
            $reasonList = "'" . implode("', '", $reasons) . "'";
            
            DB::statement("ALTER TABLE stock_adjustments ADD CONSTRAINT stock_adjustments_reason_check CHECK (reason IN ($reasonList))");
        }
    }
};
