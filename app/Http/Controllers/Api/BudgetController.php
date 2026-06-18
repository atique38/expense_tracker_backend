<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class BudgetController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $budgets = $user->budgets()
            ->with('category')
            ->latest()
            ->get();

        return response()->json([
            'status' => 200,
            'message' => 'Budgets fetched successfully.',
            'data' => $budgets,
        ], 200);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['nullable', 'integer', 'min:1'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'period' => ['required', 'in:weekly,monthly,yearly,custom'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailedResponse($validator->errors());
        }

        $validated = $validator->validated();

        $categoryId = $validated['category_id'] ?? null;
        if ($categoryId !== null) {
            $category = $user->categories()
                ->whereKey($categoryId)
                ->where('type', 'expense')
                ->first();

            if (! $category) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Budgets can only be attached to this user\'s expense categories.',
                ], 422);
            }
        }

        try {
            $budget = $user->budgets()->create($validated);

            return response()->json([
                'status' => 201,
                'message' => 'Budget created successfully.',
                'data' => $budget->load('category'),
            ], 201);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to create budget.');
        }
    }

    public function show(User $user, Budget $budget): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => 'Budget fetched successfully.',
            'data' => $budget->load('category'),
        ], 200);
    }

    public function update(Request $request, User $user, Budget $budget): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'amount' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'period' => ['sometimes', 'required', 'in:weekly,monthly,yearly,custom'],
            'start_date' => ['sometimes', 'nullable', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date'],
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

        $categoryId = array_key_exists('category_id', $validated)
            ? $validated['category_id']
            : $budget->category_id;

        if ($categoryId !== null) {
            $category = $user->categories()
                ->whereKey($categoryId)
                ->where('type', 'expense')
                ->first();

            if (! $category) {
                return response()->json([
                    'status' => 422,
                    'message' => 'Budgets can only be attached to this user\'s expense categories.',
                ], 422);
            }
        }

        try {
            $budget->update(array_merge($validated, [
                'category_id' => $categoryId,
            ]));

            return response()->json([
                'status' => 200,
                'message' => 'Budget updated successfully.',
                'data' => $budget->fresh()->load('category'),
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to update budget.');
        }
    }

    public function destroy(User $user, Budget $budget): JsonResponse
    {
        try {
            $budget->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Budget deleted successfully.',
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to delete budget.');
        }
    }
}
