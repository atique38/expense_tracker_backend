<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AccountController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $accounts = $user->accounts()->latest()->get();

        return response()->json([
            'status' => 200,
            'message' => 'Accounts fetched successfully.',
            'data' => $accounts,
        ], 200);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash,bank,card,bkash,nagad,rocket,other'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'opening_balance' => ['sometimes', 'numeric'],
            'is_default' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailedResponse($validator->errors());
        }

        $validated = $validator->validated();

        try {
            if (! empty($validated['is_default'])) {
                $user->accounts()->where('is_default', true)->update(['is_default' => false]);
            }

            $account = $user->accounts()->create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'currency' => $validated['currency'] ?? 'BDT',
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'is_default' => $validated['is_default'] ?? false,
                'notes' => $validated['notes'] ?? null,
            ]);

            return response()->json([
                'status' => 201,
                'message' => 'Account created successfully.',
                'data' => $account,
            ], 201);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to create account.');
        }
    }

    public function show(User $user, Account $account): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => 'Account fetched successfully.',
            'data' => $account,
        ], 200);
    }

    public function update(Request $request, User $user, Account $account): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:cash,bank,card,bkash,nagad,rocket,other'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'opening_balance' => ['sometimes', 'numeric'],
            'is_default' => ['sometimes', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailedResponse($validator->errors());
        }

        $validated = $validator->validated();

        if ($validated === []) {
            return $this->validationFailedResponse([
                'body' => ['At least one field must be provided.'],
            ]);
        }

        try {
            if (! empty($validated['is_default'])) {
                $user->accounts()
                    ->where('id', '!=', $account->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $account->update($validated);

            return response()->json([
                'status' => 200,
                'message' => 'Account updated successfully.',
                'data' => $account->fresh(),
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to update account.');
        }
    }

    public function destroy(User $user, Account $account): JsonResponse
    {
        try {
            $account->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Account deleted successfully.',
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to delete account.');
        }
    }
}
