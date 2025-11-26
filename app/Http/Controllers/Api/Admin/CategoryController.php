<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Category::with('subcategories');

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
        $categories = $query->paginate($perPage);

        return CategoryResource::collection($categories);
    }

    /**
     * Store a newly created category.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'is_active' => 'boolean',
            'position' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Получаем максимальную позицию для установки новой
        $maxPosition = Category::max('position') ?? 0;
        
        $category = Category::create([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'is_active' => $request->has('is_active') ? $request->is_active : true,
            'position' => $request->has('position') ? $request->position : ($maxPosition + 1),
        ]);

        return response()->json([
            'message' => 'Категория успешно создана',
            'data' => new CategoryResource($category->load('subcategories')),
        ], 201);
    }

    /**
     * Display the specified category.
     */
    public function show(string $id): CategoryResource
    {
        $category = Category::with('subcategories')->findOrFail($id);
        return new CategoryResource($category);
    }

    /**
     * Update the specified category.
     */
    public function update(Request $request, string $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
            'is_active' => 'boolean',
            'position' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        $category->update([
            'name' => $request->name,
            'slug' => $request->slug ?? Str::slug($request->name),
            'is_active' => $request->has('is_active') ? $request->is_active : $category->is_active,
            'position' => $request->has('position') ? $request->position : $category->position,
        ]);

        return response()->json([
            'message' => 'Категория успешно обновлена',
            'data' => new CategoryResource($category->load('subcategories')),
        ]);
    }

    /**
     * Remove the specified category.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        // Проверяем наличие подкатегорий
        if ($category->subcategories()->count() > 0) {
            return response()->json([
                'message' => 'Невозможно удалить категорию, так как у неё есть подкатегории',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Категория успешно удалена',
        ]);
    }
}
