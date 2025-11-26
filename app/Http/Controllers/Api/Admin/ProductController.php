<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['subcategory.category', 'images', 'primaryImage', 'variants', 'promotions']);

        // Фильтр по подкатегории
        if ($request->has('subcategory_id')) {
            $query->where('subcategory_id', $request->subcategory_id);
        }

        // Фильтр по категории
        if ($request->has('category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Фильтр по наличию
        if ($request->has('in_stock')) {
            $query->where('in_stock', filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN));
        }

        // Фильтр по типу
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Фильтр по целевому полу
        if ($request->has('gender_target')) {
            $query->where('gender_target', $request->gender_target);
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = $request->get('per_page', 15);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'type' => 'nullable|string|max:255',
            'gender_target' => 'nullable|string|max:255',
            'volume' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'in_stock' => 'boolean',
            'stock_qty' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'images' => 'nullable|array',
            'images.*.url' => 'required|string',
            'images.*.is_primary' => 'boolean',
            'images.*.order' => 'integer',
            'variants' => 'nullable|array',
            'variants.*.variant_name' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_qty' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $product = Product::create([
                'subcategory_id' => $request->subcategory_id,
                'name' => $request->name,
                'sku' => $request->sku,
                'type' => $request->type,
                'gender_target' => $request->gender_target,
                'volume' => $request->volume,
                'price' => $request->price,
                'currency' => $request->currency ?? 'RUB',
                'in_stock' => $request->in_stock ?? true,
                'stock_qty' => $request->stock_qty,
                'description' => $request->description,
                'tags' => $request->tags ?? [],
            ]);

            // Добавляем изображения
            if ($request->has('images') && is_array($request->images)) {
                foreach ($request->images as $index => $imageData) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $imageData['url'],
                        'is_primary' => $imageData['is_primary'] ?? ($index === 0),
                        'order' => $imageData['order'] ?? $index,
                    ]);
                }
            }

            // Добавляем варианты
            if ($request->has('variants') && is_array($request->variants)) {
                foreach ($request->variants as $variantData) {
                    ProductVariant::create([
                        'product_id' => $product->id,
                        'variant_name' => $variantData['variant_name'],
                        'price' => $variantData['price'],
                        'stock_qty' => $variantData['stock_qty'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Товар успешно создан',
                'data' => new ProductResource($product->load(['subcategory.category', 'images', 'primaryImage', 'variants', 'promotions'])),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при создании товара',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show(string $id): ProductResource
    {
        $product = Product::with(['subcategory.category', 'images', 'primaryImage', 'variants', 'promotions'])
            ->findOrFail($id);
        return new ProductResource($product);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'subcategory_id' => 'required|exists:subcategories,id',
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:255|unique:products,sku,' . $id,
            'type' => 'nullable|string|max:255',
            'gender_target' => 'nullable|string|max:255',
            'volume' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'in_stock' => 'boolean',
            'stock_qty' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'images' => 'nullable|array',
            'images.*.id' => [
                'nullable',
                function ($attribute, $value, $fail) use ($product) {
                    if ($value !== null && $value !== '') {
                        // Проверяем, что ID существует в product_images и принадлежит этому товару
                        $exists = ProductImage::where('id', $value)
                            ->where('product_id', $product->id)
                            ->exists();
                        if (!$exists) {
                            $fail('The selected ' . $attribute . ' is invalid.');
                        }
                    }
                }
            ],
            'images.*.url' => 'required|string',
            'images.*.is_primary' => 'boolean',
            'images.*.order' => 'integer',
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|exists:product_variants,id',
            'variants.*.variant_name' => 'required|string',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.stock_qty' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $product->update([
                'subcategory_id' => $request->subcategory_id,
                'name' => $request->name,
                'sku' => $request->sku,
                'type' => $request->type,
                'gender_target' => $request->gender_target,
                'volume' => $request->volume,
                'price' => $request->price,
                'currency' => $request->currency ?? 'RUB',
                'in_stock' => $request->in_stock ?? true,
                'stock_qty' => $request->stock_qty,
                'description' => $request->description,
                'tags' => $request->tags ?? [],
            ]);

            // Обновляем изображения
            if ($request->has('images')) {
                // Получаем ID существующих изображений
                $existingImageIds = collect($request->images)->pluck('id')->filter();
                
                // Удаляем изображения, которых нет в запросе
                ProductImage::where('product_id', $product->id)
                    ->whereNotIn('id', $existingImageIds)
                    ->delete();

                // Обновляем или создаем изображения
                foreach ($request->images as $index => $imageData) {
                    if (isset($imageData['id'])) {
                        // Обновляем существующее
                        ProductImage::where('id', $imageData['id'])
                            ->where('product_id', $product->id)
                            ->update([
                                'url' => $imageData['url'],
                                'is_primary' => $imageData['is_primary'] ?? false,
                                'order' => $imageData['order'] ?? $index,
                            ]);
                    } else {
                        // Создаем новое
                        ProductImage::create([
                            'product_id' => $product->id,
                            'url' => $imageData['url'],
                            'is_primary' => $imageData['is_primary'] ?? ($index === 0),
                            'order' => $imageData['order'] ?? $index,
                        ]);
                    }
                }

                // Убеждаемся, что только одно изображение основное
                $primaryCount = ProductImage::where('product_id', $product->id)
                    ->where('is_primary', true)
                    ->count();
                
                if ($primaryCount === 0 && ProductImage::where('product_id', $product->id)->count() > 0) {
                    ProductImage::where('product_id', $product->id)
                        ->orderBy('order')
                        ->first()
                        ->update(['is_primary' => true]);
                }
            }

            // Обновляем варианты
            if ($request->has('variants')) {
                // Получаем ID существующих вариантов
                $existingVariantIds = collect($request->variants)->pluck('id')->filter();
                
                // Удаляем варианты, которых нет в запросе
                ProductVariant::where('product_id', $product->id)
                    ->whereNotIn('id', $existingVariantIds)
                    ->delete();

                // Обновляем или создаем варианты
                foreach ($request->variants as $variantData) {
                    if (isset($variantData['id'])) {
                        // Обновляем существующий
                        ProductVariant::where('id', $variantData['id'])
                            ->where('product_id', $product->id)
                            ->update([
                                'variant_name' => $variantData['variant_name'],
                                'price' => $variantData['price'],
                                'stock_qty' => $variantData['stock_qty'] ?? null,
                            ]);
                    } else {
                        // Создаем новый
                        ProductVariant::create([
                            'product_id' => $product->id,
                            'variant_name' => $variantData['variant_name'],
                            'price' => $variantData['price'],
                            'stock_qty' => $variantData['stock_qty'] ?? null,
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Товар успешно обновлен',
                'data' => new ProductResource($product->load(['subcategory.category', 'images', 'primaryImage', 'variants', 'promotions'])),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Ошибка при обновлении товара',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Товар успешно удален',
        ]);
    }
}
