<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PromotionResource;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    /**
     * Display a listing of promotions.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Promotion::with('products');

        // Фильтр по активности
        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $promotions = $query->paginate($perPage);

        return PromotionResource::collection($promotions);
    }

    /**
     * Store a newly created promotion.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $promotion = Promotion::create([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? true,
            ]);

            // Привязываем товары
            if ($request->has('product_ids') && is_array($request->product_ids)) {
                $promotion->products()->attach($request->product_ids);
            }

            DB::commit();

            return response()->json([
                'message' => 'Промо-акция успешно создана',
                'data' => new PromotionResource($promotion->load('products')),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при создании промо-акции',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified promotion.
     */
    public function show(string $id): PromotionResource
    {
        $promotion = Promotion::with('products')->findOrFail($id);
        return new PromotionResource($promotion);
    }

    /**
     * Update the specified promotion.
     */
    public function update(Request $request, string $id)
    {
        $promotion = Promotion::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $promotion->update([
                'name' => $request->name,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active ?? true,
            ]);

            // Обновляем привязку товаров
            if ($request->has('product_ids')) {
                $promotion->products()->sync($request->product_ids);
            }

            DB::commit();

            return response()->json([
                'message' => 'Промо-акция успешно обновлена',
                'data' => new PromotionResource($promotion->load('products')),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении промо-акции',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified promotion.
     */
    public function destroy(string $id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return response()->json([
            'message' => 'Промо-акция успешно удалена',
        ]);
    }
}
