<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class ApiMobileController extends Controller
{
    protected function ok(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json($payload, $status);
    }

    protected function message(string $message, bool $success = true, int $status = 200, int $retryAfter = 0): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'retry_after' => $retryAfter,
        ], $status);
    }

    protected function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    protected function appCurrency(): string
    {
        try {
            $currency = strtoupper((string) gs('cur_text'));
            if ($currency !== '') {
                return $currency;
            }
        } catch (\Throwable) {
            // Fall back to static default when settings are unavailable.
        }

        return 'USD';
    }
}
