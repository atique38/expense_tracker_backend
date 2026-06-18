<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\MessageBag;
use Throwable;

abstract class Controller
{
    protected function validationFailedResponse(array|MessageBag $errors): JsonResponse
    {
        return response()->json([
            'status' => 422,
            'message' => 'Validation failed.',
            'errors' => $errors,
        ], 422);
    }

    protected function exceptionResponse(Throwable $throwable, string $message): JsonResponse
    {
        return response()->json([
            'status' => 500,
            'message' => $message,
            'error' => $throwable->getMessage(),
        ], 500);
    }
}
