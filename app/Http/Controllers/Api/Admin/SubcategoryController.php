<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubcategoryResource;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubcategoryController extends Controller
{
    /**
     * Display a listing of subcategories.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Subcategory::with('category');

        // Фильтр по категории
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $subcategories = $query->paginate($perPage);

        return SubcategoryResource::collection($subcategories);
    }

    /**
     * Store a newly created subcategory.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'position' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Проверка уникальности slug в рамках категории
        $slug = $request->slug ?? Str::slug($request->name);
        $exists = Subcategory::where('category_id', $request->category_id)
            ->where('slug', $slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Подкатегория с таким slug уже существует в данной категории',
                'errors' => ['slug' => ['Подкатегория с таким slug уже существует']],
            ], 422);
        }

        // Получаем максимальную позицию для данной категории
        $maxPosition = Subcategory::where('category_id', $request->category_id)->max('position') ?? 0;

        $subcategory = Subcategory::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'position' => $request->has('position') ? $request->position : ($maxPosition + 1),
        ]);

        return response()->json([
            'message' => 'Подкатегория успешно создана',
            'data' => new SubcategoryResource($subcategory->load('category')),
        ], 201);
    }

    /**
     * Display the specified subcategory.
     */
    public function show(string $id): SubcategoryResource
    {
        $subcategory = Subcategory::with('category')->findOrFail($id);
        return new SubcategoryResource($subcategory);
    }

    /**
     * Update the specified subcategory.
     */
    public function update(Request $request, string $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'position' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Проверка уникальности slug в рамках категории
        $slug = $request->slug ?? Str::slug($request->name);
        $exists = Subcategory::where('category_id', $request->category_id)
            ->where('slug', $slug)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Подкатегория с таким slug уже существует в данной категории',
                'errors' => ['slug' => ['Подкатегория с таким slug уже существует']],
            ], 422);
        }

        $subcategory->update([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $slug,
            'is_active' => $request->has('is_active') ? $request->is_active : $subcategory->is_active,
            'position' => $request->has('position') ? $request->position : $subcategory->position,
        ]);

        return response()->json([
            'message' => 'Подкатегория успешно обновлена',
            'data' => new SubcategoryResource($subcategory->load('category')),
        ]);
    }

    /**
     * Remove the specified subcategory.
     */
    public function destroy(string $id)
    {
        $subcategory = Subcategory::findOrFail($id);

        // Проверяем наличие товаров
        if ($subcategory->products()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить подкатегорию, так как у неё есть товары',
            ], 422);
        }

        $subcategory->delete();

        return response()->json([
            'message' => 'Подкатегория успешно удалена',
        ]);
    }
}
