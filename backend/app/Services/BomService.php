<?php

namespace App\Services;

use App\Models\Bom;
use Illuminate\Support\Facades\DB;

class BomService
{
    /**
     * Create BOM with lines and operations
     */
    public function create(array $data): Bom
    {
        return DB::transaction(function () use ($data) {
            $bom = Bom::create([
                'product_id' => $data['product_id'],
                'type' => $data['type'] ?? 'normal',
                'qty_produced' => $data['qty_produced'] ?? 1,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Create BOM lines and map temporary IDs to real IDs
            $lineIdMap = []; // Maps frontend temp IDs to real DB IDs
            if (isset($data['lines'])) {
                foreach ($data['lines'] as $index => $line) {
                    $newLine = $bom->lines()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'sequence' => $line['sequence'] ?? $index,
                    ]);
                    // Map temporary ID to real ID
                    if (isset($line['id']) && is_string($line['id'])) {
                        $lineIdMap[$line['id']] = $newLine->id;
                    }
                }
            }

            // Create operations - resolve temporary line IDs to real IDs
            if (isset($data['operations'])) {
                foreach ($data['operations'] as $index => $op) {
                    $producesBomLineId = $op['produces_bom_line_id'] ?? null;

                    // If it's a string (temporary ID), look up the real ID
                    if (is_string($producesBomLineId) && isset($lineIdMap[$producesBomLineId])) {
                        $producesBomLineId = $lineIdMap[$producesBomLineId];
                    } elseif (is_string($producesBomLineId)) {
                        // Temporary ID not found - set to null
                        $producesBomLineId = null;
                    }

                    $bomOp = $bom->operations()->create([
                        'work_center_id' => $op['work_center_id'],
                        'name' => $op['name'],
                        'sequence' => $op['sequence'] ?? $index,
                        'duration_minutes' => $op['duration_minutes'] ?? 0,
                        'needs_quality_check' => $op['needs_quality_check'] ?? false,
                        'instruction_file_url' => $op['instruction_file_url'] ?? null,
                        'produces_bom_line_id' => $producesBomLineId,
                    ]);
                }
            }

            return $bom->load(['lines.product', 'operations.workCenter', 'operations.producesBomLine.product']);
        });
    }

    /**
     * Update BOM with lines and operations
     */
    public function update(Bom $bom, array $data): Bom
    {
        return DB::transaction(function () use ($bom, $data) {
            $bom->update($data);

            // Update lines if provided - track new lines for ID mapping
            $lineIdMap = []; // Maps temporary IDs to real IDs
            if (isset($data['lines'])) {
                $existingIds = [];
                foreach ($data['lines'] as $index => $line) {
                    if (isset($line['id']) && is_numeric($line['id'])) {
                        // Update existing line
                        $bom->lines()->where('id', $line['id'])->update([
                            'product_id' => $line['product_id'],
                            'quantity' => $line['quantity'],
                            'sequence' => $line['sequence'] ?? $index,
                        ]);
                        $existingIds[] = $line['id'];
                    } else {
                        // Create new line
                        $newLine = $bom->lines()->create([
                            'product_id' => $line['product_id'],
                            'quantity' => $line['quantity'],
                            'sequence' => $line['sequence'] ?? $index,
                        ]);
                        $existingIds[] = $newLine->id;
                        
                        // Map temporary ID to real ID
                        if (isset($line['id']) && is_string($line['id'])) {
                            $lineIdMap[$line['id']] = $newLine->id;
                        }
                    }
                }
                $bom->lines()->whereNotIn('id', $existingIds)->delete();
            }

            // Update operations if provided - resolve temporary line IDs
            if (isset($data['operations'])) {
                $existingIds = [];
                foreach ($data['operations'] as $index => $op) {
                    $producesBomLineId = $op['produces_bom_line_id'] ?? null;
                    
                    // Resolve temporary line ID to real ID
                    if (is_string($producesBomLineId) && isset($lineIdMap[$producesBomLineId])) {
                        $producesBomLineId = $lineIdMap[$producesBomLineId];
                    } elseif (is_string($producesBomLineId)) {
                        // Not found - set to null
                        $producesBomLineId = null;
                    }
                    
                    if (isset($op['id'])) {
                        $bom->operations()->where('id', $op['id'])->update([
                            'name' => $op['name'],
                            'work_center_id' => $op['work_center_id'],
                            'duration_minutes' => $op['duration_minutes'] ?? 0,
                            'sequence' => $op['sequence'] ?? $index,
                            'needs_quality_check' => $op['needs_quality_check'] ?? false,
                            'instruction_file_url' => $op['instruction_file_url'] ?? null,
                            'produces_bom_line_id' => $producesBomLineId,
                        ]);
                        $existingIds[] = $op['id'];

                    } else {
                        $newOp = $bom->operations()->create([
                            'name' => $op['name'],
                            'work_center_id' => $op['work_center_id'],
                            'duration_minutes' => $op['duration_minutes'] ?? 0,
                            'sequence' => $op['sequence'] ?? $index,
                            'needs_quality_check' => $op['needs_quality_check'] ?? false,
                            'instruction_file_url' => $op['instruction_file_url'] ?? null,
                            'produces_bom_line_id' => $op['produces_bom_line_id'] ?? null,
                        ]);

                        $existingIds[] = $newOp->id;
                    }
                }
                // Remove operations not in the update
                $bom->operations()->whereNotIn('id', $existingIds)->delete();
            }

            return $bom->load(['lines.product', 'operations.workCenter', 'operations.producesBomLine.product']);
        });
    }
}
