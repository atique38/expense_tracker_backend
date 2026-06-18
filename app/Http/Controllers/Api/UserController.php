<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UserController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'string', 'max:11', 'min:11'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailedResponse($validator->errors());
        }

        $validated = $validator->validated();

        try {
            $user = User::firstOrCreate(
                ['phone' => $validated['phone']],
                ['name' => $validated['name'] ?? null]
            );

            $created = $user->wasRecentlyCreated;

            if ($request->filled('name') && empty($user->name)) {
                $user->update([
                    'name' => $validated['name'],
                ]);
                $user->refresh();
            }

            return response()->json([
                'status' => $created ? 201 : 200,
                'message' => $created ? 'User created successfully.' : 'User logged in successfully.',
                'data' => $user,
            ], $created ? 201 : 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to log in user.');
        }
    }
}
