<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class CategoryController extends Controller
{
    public function index(User $user): JsonResponse
    {
        $categories = $user->categories()->latest()->get();

        return response()->json([
            'status' => 200,
            'message' => 'Categories fetched successfully.',
            'data' => $categories,
        ], 200);
    }

    public function store(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense'],
        ]);

        if ($validator->fails()) {
            return $this->validationFailedResponse($validator->errors());
        }

        $validated = $validator->validated();

        try {
            $category = $user->categories()->create($validated);

            return response()->json([
                'status' => 201,
                'message' => 'Category created successfully.',
                'data' => $category,
            ], 201);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to create category.');
        }
    }

    public function show(User $user, Category $category): JsonResponse
    {
        return response()->json([
            'status' => 200,
            'message' => 'Category fetched successfully.',
            'data' => $category,
        ], 200);
    }

    public function update(Request $request, User $user, Category $category): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'type' => ['sometimes', 'required', 'in:income,expense'],
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
            $category->update($validated);

            return response()->json([
                'status' => 200,
                'message' => 'Category updated successfully.',
                'data' => $category->fresh(),
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to update category.');
        }
    }

    public function destroy(User $user, Category $category): JsonResponse
    {
        try {
            $category->delete();

            return response()->json([
                'status' => 200,
                'message' => 'Category deleted successfully.',
            ], 200);
        } catch (Throwable $throwable) {
            return $this->exceptionResponse($throwable, 'Failed to delete category.');
        }
    }
}
