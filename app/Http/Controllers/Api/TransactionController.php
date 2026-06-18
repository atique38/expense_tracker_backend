<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TransactionController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $transactions = $user->transactions()
            ->with(['account', 'category'])
            ->latest('transaction_date')
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Transactions fetched successfully.',
            'data' => $transactions,
        ], 200);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => ['required', 'integer', 'min:1'],
            'category_id' => ['nullable', 'integer', 'min:1'],
            'transaction_type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'transaction_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailedResponse($validator->errors());
        }

        $validated = $validator->validated();

        $account = $user->accounts()->whereKey($validated['account_id'])->first();

        if (! $account) {
            return response()->json([
                'status' => 422,
                'message' => 'Selected account does not belong to this user.',
            ], 422);
        }

        $categoryId = $validated['category_id'] ?? null;
        if ($categoryId !== null) {
            $category = $user->categories()->whereKey($categoryId)->first();

            if (! $category) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Selected category does not belong to this user.',
                ], 422);
            }

            if ($category->type !== $validated['transaction_type']) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Transaction type and category type must match.',
                ], 422);
            }
        }

        try {
            $transaction = $user->transactions()->create($validated);

            return response()->json([
                'status' => 201,
                'message' => 'Transaction created successfully.',
                'data' => $transaction->load(['account', 'category']),
            ], 201);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to create transaction.');
        }
    }

    public function show(User $user, Transaction $transaction): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => 'Transaction fetched successfully.',
            'data' => $transaction->load(['account', 'category']),
        ], 200);
    }

    public function update(Request $request, User $user, Transaction $transaction): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'account_id' => ['sometimes', 'required', 'integer', 'min:1'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'transaction_type' => ['sometimes', 'required', 'in:income,expense'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'transaction_date' => ['sometimes', 'required', 'date'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'reference' => ['sometimes', 'nullable', 'string', 'max:100'],
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

        $accountId = $validated['account_id'] ?? $transaction->account_id;
        $account = $user->accounts()->whereKey($accountId)->first();

        if (! $account) {
            return response()->json([
                'status' => 422,
                'message' => 'Selected account does not belong to this user.',
            ], 422);
        }

        $transactionType = $validated['transaction_type'] ?? $transaction->transaction_type;
        $categoryId = array_key_exists('category_id', $validated)
            ? $validated['category_id']
            : $transaction->category_id;

        if ($categoryId !== null) {
            $category = $user->categories()->whereKey($categoryId)->first();

            if (! $category) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Selected category does not belong to this user.',
                ], 422);
            }

            if ($category->type !== $transactionType) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Transaction type and category type must match.',
                ], 422);
            }
        }

        try {
            $transaction->update(array_merge($validated, [
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'transaction_type' => $transactionType,
            ]));

            return response()->json([
                'status' => 200,
                'message' => 'Transaction updated successfully.',
                'data' => $transaction->fresh()->load(['account', 'category']),
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to update transaction.');
        }
    }

    public function destroy(User $user, Transaction $transaction): JsonResponse
    {
        try {
            $transaction->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Transaction deleted successfully.',
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to delete transaction.');
        }
    }
}
