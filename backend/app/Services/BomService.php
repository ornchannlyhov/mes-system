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

            // Create BOM lines
            if (isset($data['lines'])) {
                foreach ($data['lines'] as $index => $line) {
                    $bom->lines()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'sequence' => $line['sequence'] ?? $index,
                    ]);
                }
            }

            // Create operations
            if (isset($data['operations'])) {
                foreach ($data['operations'] as $index => $op) {
                    $bomOp = $bom->operations()->create([
                        'work_center_id' => $op['work_center_id'],
                        'name' => $op['name'],
                        'sequence' => $op['sequence'] ?? $index,
                        'duration_minutes' => $op['duration_minutes'] ?? 0,
                        'needs_quality_check' => $op['needs_quality_check'] ?? false,
                        'instruction_file_url' => $op['instruction_file_url'] ?? null,
                    ]);

                    // Create quality checks
                    if (isset($op['quality_checks'])) {
                        foreach ($op['quality_checks'] as $qc) {
                            $bomOp->qualityChecks()->create([
                                'label' => $qc['label'],
                                'type' => $qc['type'],
                                'description' => $qc['description'] ?? null,
                                'instructions' => $qc['instructions'] ?? null,
                                'min_value' => $qc['min_value'] ?? null,
                                'max_value' => $qc['max_value'] ?? null,
                                'organization_id' => $bom->organization_id ?? 1, // Assumption, or get from context
                            ]);
                        }
                    }
                }
            }

            return $bom->load(['lines.product', 'operations.workCenter']);
        });
    }

    /**
     * Update BOM with lines and operations
     */
    public function update(Bom $bom, array $data): Bom
    {
        return DB::transaction(function () use ($bom, $data) {
            $bom->update($data);

            // Update lines if provided
            if (isset($data['lines'])) {
                $existingIds = [];
                foreach ($data['lines'] as $index => $line) {
                    if (isset($line['id'])) {
                        $bom->lines()->where('id', $line['id'])->update([
                            'product_id' => $line['product_id'],
                            'quantity' => $line['quantity'],
                            'sequence' => $line['sequence'] ?? $index,
                        ]);
                        $existingIds[] = $line['id'];
                    } else {
                        $newLine = $bom->lines()->create([
                            'product_id' => $line['product_id'],
                            'quantity' => $line['quantity'],
                            'sequence' => $line['sequence'] ?? $index,
                        ]);
                        $existingIds[] = $newLine->id;
                    }
                }
                $bom->lines()->whereNotIn('id', $existingIds)->delete();
            }

            // Update operations if provided
            if (isset($data['operations'])) {
                $existingIds = [];
                foreach ($data['operations'] as $index => $op) {
                    if (isset($op['id'])) {
                        $bom->operations()->where('id', $op['id'])->update([
                            'name' => $op['name'],
                            'work_center_id' => $op['work_center_id'],
                            'duration_minutes' => $op['duration_minutes'] ?? 0,
                            'sequence' => $op['sequence'] ?? $index,
                            'needs_quality_check' => $op['needs_quality_check'] ?? false,
                            'instruction_file_url' => $op['instruction_file_url'] ?? null,
                        ]);
                        $existingIds[] = $op['id'];

                        // Handle Nested Quality Checks for Existing Operation
                        /** @var \App\Models\Operation $opModel */
                        $opModel = $bom->operations()->where('id', $op['id'])->first();
                        if ($opModel && isset($op['quality_checks'])) {
                            $existingQcIds = [];
                            foreach ($op['quality_checks'] as $qc) {
                                if (isset($qc['id'])) {
                                    $opModel->qualityChecks()->where('id', $qc['id'])->update([
                                        'label' => $qc['label'],
                                        'type' => $qc['type'],
                                        'description' => $qc['description'] ?? null,
                                        'instructions' => $qc['instructions'] ?? null,
                                        'min_value' => $qc['min_value'] ?? null,
                                        'max_value' => $qc['max_value'] ?? null,
                                    ]);
                                    $existingQcIds[] = $qc['id'];
                                } else {
                                    $newQc = $opModel->qualityChecks()->create([
                                        'label' => $qc['label'],
                                        'type' => $qc['type'],
                                        'description' => $qc['description'] ?? null,
                                        'instructions' => $qc['instructions'] ?? null,
                                        'min_value' => $qc['min_value'] ?? null,
                                        'max_value' => $qc['max_value'] ?? null,
                                        'organization_id' => $bom->organization_id ?? 1,
                                    ]);
                                    $existingQcIds[] = $newQc->id;
                                }
                            }
                            $opModel->qualityChecks()->whereNotIn('id', $existingQcIds)->delete();
                        } elseif ($opModel && (!isset($op['quality_checks']) || empty($op['quality_checks']))) {
                            // If quality_checks field is present but empty, delete all? 
                            // Or if 'quality_checks' matches null?
                            // Safest is to only delete if the key 'quality_checks' is expressly provided
                            if (array_key_exists('quality_checks', $op)) {
                                $opModel->qualityChecks()->delete();
                            }
                        }

                    } else {
                        $newOp = $bom->operations()->create([
                            'name' => $op['name'],
                            'work_center_id' => $op['work_center_id'],
                            'duration_minutes' => $op['duration_minutes'] ?? 0,
                            'sequence' => $op['sequence'] ?? $index,
                            'needs_quality_check' => $op['needs_quality_check'] ?? false,
                            'instruction_file_url' => $op['instruction_file_url'] ?? null,
                        ]);

                        // Handle nested Quality Checks for New Operation
                        if (isset($op['quality_checks'])) {
                            foreach ($op['quality_checks'] as $qc) {
                                $newOp->qualityChecks()->create([
                                    'label' => $qc['label'],
                                    'type' => $qc['type'],
                                    'description' => $qc['description'] ?? null,
                                    'instructions' => $qc['instructions'] ?? null,
                                    'min_value' => $qc['min_value'] ?? null,
                                    'max_value' => $qc['max_value'] ?? null,
                                    'organization_id' => $bom->organization_id ?? 1,
                                ]);
                            }
                        }

                        $existingIds[] = $newOp->id;
                    }
                }
                // Remove operations not in the update
                $bom->operations()->whereNotIn('id', $existingIds)->delete();
            }

            return $bom->load(['lines.product', 'operations.workCenter']);
        });
    }
}
