<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BaseController extends Controller
{
    /**
     * Standard success response.
     *
     * @param mixed $data Content of the response
     * @param array $meta Metadata (e.g., pagination, counts, filters)
     * @param int $code HTTP Status Code
     * @return JsonResponse
     */
    protected function success($data = [], array $meta = [], int $code = 200): JsonResponse
    {
        $response = ['data' => $data];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $code);
    }

    /**
     * Standard error response.
     *
     * @param string $message Error message
     * @param int $code HTTP Status Code
     * @param array $errors Validation errors or detailed info
     * @return JsonResponse
     */
    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        $response = ['message' => $message];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    /**
     * Helper to return a paginated response with optional extra meta.
     *
     * @param LengthAwarePaginator $paginator
     * @param array $extraMeta
     * @return JsonResponse
     */
    protected function respondWithPagination(LengthAwarePaginator $paginator, array $extraMeta = []): JsonResponse
    {
        $data = $paginator->items();

        $meta = array_merge([
            'current_page' => $paginator->currentPage(),
            'from' => $paginator->firstItem(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'to' => $paginator->lastItem(),
            'total' => $paginator->total(),
        ], $extraMeta);

        return $this->success($data, $meta);
    }

    /**
     * Helper to get status counts for filters (e.g., Tabs).
     *
     * @param Builder $query Base query (before filtering by status)
     * @param string $groupByField Field to group by (e.g., 'status', 'type')
     * @return array Key-value pairs of counts (e.g., ['draft' => 5, 'done' => 10])
     */
    protected function getStatusCounts(Builder $query, string $groupByField): array
    {
        // Clone the query to avoid modifying the original referenced query
        $countQuery = $query->clone();

        // Remove existing order by clauses as they break grouping
        $countQuery->reorder();

        $counts = $countQuery->select($groupByField, \DB::raw('count(*) as count'))
            ->groupBy($groupByField)
            ->pluck('count', $groupByField)
            ->toArray();

        $counts['all'] = array_sum($counts);

        return $counts;
    }
}
