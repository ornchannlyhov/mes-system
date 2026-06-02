<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Searchable
{
    /**
     * Apply standard filters, search, and sorting to the query.
     *
     * @param Builder $query
     * @param Request $request
     * @param array $searchableFields Fields to search in (e.g., ['name', 'code'])
     * @param array $filterableFields Exact match filters (e.g., ['status', 'type'])
     * @return Builder
     */
    /**
     * Columns always safe to sort by regardless of model.
     * Controllers pass $allowedSortColumns to extend this list.
     */
    private static array $baseSortColumns = [
        'id', 'created_at', 'updated_at', 'name', 'code', 'status',
        'priority', 'scheduled_start', 'scheduled_end', 'is_active',
    ];

    public function scopeApplyStandardFilters(Builder $query, Request $request, array $searchableFields = [], array $filterableFields = [], array $allowedSortColumns = [])
    {
        // 1. Exact Filters
        foreach ($filterableFields as $field) {
            if ($request->has($field) && $request->input($field) !== null && $request->input($field) !== '') {
                $query->where($field, $request->input($field));
            }
        }

        // 2. Search (Partial Match)
        if ($request->has('search') && $request->input('search') && !empty($searchableFields)) {
            $searchTerm = strtolower($request->input('search'));
            $query->where(function ($q) use ($searchableFields, $searchTerm) {
                foreach ($searchableFields as $index => $field) {
                    if ($index === 0) {
                        $q->whereRaw("LOWER({$field}) LIKE ?", ["%{$searchTerm}%"]);
                    } else {
                        $q->orWhereRaw("LOWER({$field}) LIKE ?", ["%{$searchTerm}%"]);
                    }
                }
            });
        }

        // 3. Sorting — allowlist prevents SQL injection via unsanitized sort_by input
        $allowed = array_merge(self::$baseSortColumns, $allowedSortColumns);
        $sortColumn = $request->input('sort_by', 'created_at');
        if (!in_array($sortColumn, $allowed, true)) {
            $sortColumn = 'created_at';
        }

        $sortDirection = in_array(strtolower((string) $request->input('sort_order', 'desc')), ['asc', 'desc'])
            ? $request->input('sort_order', 'desc')
            : 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        return $query;
    }
}
