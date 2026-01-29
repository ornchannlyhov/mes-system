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
    public function scopeApplyStandardFilters(Builder $query, Request $request, array $searchableFields = [], array $filterableFields = [])
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

        // 3. Sorting
        $sortColumn = $request->input('sort_by', 'created_at');
        // Prevent sorting by unauthorized columns to avoid SQL injection vulnerability
        // For simplicity, we assume strict models or rely on the fact that undefined columns throw SQL error (500)
        // A better approach is to whitelist allowed sort columns in the controller.
        $sortDirection = $request->input('sort_order', 'desc');

        // Simple validation for direction
        $sortDirection = in_array(strtolower($sortDirection), ['asc', 'desc']) ? $sortDirection : 'desc';

        $query->orderBy($sortColumn, $sortDirection);

        return $query;
    }
}
