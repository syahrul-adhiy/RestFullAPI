<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Send a standardized success JSON response.
     */
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operasi berhasil dilakukan',
        int $statusCode = 200,
        ?array $meta = null
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        if ($meta !== null) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Send a standardized error JSON response.
     */
    protected function errorResponse(
        string $message = 'Terjadi kesalahan pada permintaan',
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
}
