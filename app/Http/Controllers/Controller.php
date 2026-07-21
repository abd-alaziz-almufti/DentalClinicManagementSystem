<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

abstract class Controller
{
    /**
     * Respond with a success envelope.
     */
    protected function respondSuccess(mixed $data = null, string $message = 'Success'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Respond with a paginated collection envelope.
     */
    protected function respondPaginated(mixed $paginator, string $message = 'Success'): JsonResponse
    {
        if ($paginator instanceof AnonymousResourceCollection) {
            $data = $paginator->response()->getData(true);
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data['data'] ?? [],
                'meta' => [
                    'current_page' => $data['meta']['current_page'] ?? 1,
                    'per_page' => $data['meta']['per_page'] ?? 20,
                    'total' => $data['meta']['total'] ?? 0,
                    'last_page' => $data['meta']['last_page'] ?? 1,
                ],
            ]);
        }

        if ($paginator instanceof LengthAwarePaginator) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator,
        ]);
    }
}
