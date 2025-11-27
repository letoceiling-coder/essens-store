<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Product::with(['subcategory.category', 'images', 'primaryImage', 'variants', 'promotions'])
            ->whereHas('subcategory', function ($q) {
                $q->where('is_active', true)
                  ->whereHas('category', function ($catQuery) {
                      $catQuery->where('is_active', true);
                  });
            });

        // Фильтрация по подкатегории (поддержка множественных значений)
        if ($request->has('subcategory_id')) {
            $subcategoryIds = is_array($request->subcategory_id) 
                ? $request->subcategory_id 
                : explode(',', $request->subcategory_id);
            $subcategoryIds = array_filter(array_map('intval', $subcategoryIds));
            if (!empty($subcategoryIds)) {
                $query->whereHas('subcategory', function ($q) use ($subcategoryIds) {
                    $q->whereIn('id', $subcategoryIds)
                      ->where('is_active', true)
                      ->whereHas('category', function ($catQuery) {
                          $catQuery->where('is_active', true);
                      });
                });
            }
        }

        // Фильтрация по категории (через подкатегорию)
        if ($request->has('category_id')) {
            $query->whereHas('subcategory', function ($q) use ($request) {
                $q->where('category_id', $request->category_id)
                  ->where('is_active', true)
                  ->whereHas('category', function ($catQuery) {
                      $catQuery->where('is_active', true);
                  });
            });
        }

        // Фильтрация по наличию
        if ($request->has('in_stock')) {
            $query->where('in_stock', filter_var($request->in_stock, FILTER_VALIDATE_BOOLEAN));
        }

        // Фильтрация по типу (поддержка множественных значений)
        if ($request->has('type')) {
            $types = is_array($request->type) 
                ? $request->type 
                : explode(',', $request->type);
            $types = array_filter($types);
            if (!empty($types)) {
                $query->whereIn('type', $types);
            }
        }

        // Фильтрация по целевому полу (поддержка множественных значений)
        if ($request->has('gender_target')) {
            $genders = is_array($request->gender_target) 
                ? $request->gender_target 
                : explode(',', $request->gender_target);
            $genders = array_filter($genders);
            if (!empty($genders)) {
                $query->whereIn('gender_target', $genders);
            }
        }

        // Поиск по названию
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Фильтрация по тегам
        if ($request->has('tags')) {
            $tags = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->whereJsonContains('tags', $tags);
        }

        // Фильтрация по цене
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'price', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Пагинация
        $perPage = $request->get('per_page', 12);
        $products = $query->paginate($perPage);

        return ProductResource::collection($products);
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug): ProductResource
    {
        // Поддерживаем как slug, так и id для обратной совместимости
        $product = Product::with([
            'subcategory.category',
            'images',
            'primaryImage',
            'variants',
            'promotions'
        ])->where(function ($query) use ($slug) {
            $query->where('slug', $slug)
                  ->orWhere('id', $slug);
        })->firstOrFail();
        
        return new ProductResource($product);
    }

    /**
     * Get product by SKU.
     */
    public function showBySku(string $sku): ProductResource
    {
        $product = Product::with([
            'subcategory.category',
            'images',
            'primaryImage',
            'variants',
            'promotions'
        ])->where('sku', $sku)->firstOrFail();
        
        return new ProductResource($product);
    }

    /**
     * Get featured/popular products.
     */
    public function featured(Request $request): AnonymousResourceCollection
    {
        $limit = $request->get('limit', 8);
        
        $products = Product::with(['subcategory.category', 'images', 'primaryImage'])
            ->where('in_stock', true)
            ->whereHas('images')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
        
        return ProductResource::collection($products);
    }
}
