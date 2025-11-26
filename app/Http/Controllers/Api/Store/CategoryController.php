<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with subcategories.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::with(['subcategories' => function ($query) {
            $query->where('is_active', true)->orderBy('position');
        }])
        ->where('is_active', true)
        ->orderBy('position')
        ->get();
        
        return CategoryResource::collection($categories);
    }

    /**
     * Display the specified category.
     */
    public function show(string $id): CategoryResource
    {
        $category = Category::with(['subcategories' => function ($query) {
            $query->where('is_active', true)->orderBy('position');
        }])
        ->where('is_active', true)
        ->findOrFail($id);
        
        return new CategoryResource($category);
    }

    /**
     * Get category by slug.
     */
    public function showBySlug(string $slug): CategoryResource
    {
        $category = Category::with(['subcategories' => function ($query) {
            $query->where('is_active', true)->orderBy('position');
        }])
        ->where('is_active', true)
        ->where('slug', $slug)
        ->firstOrFail();
        
        return new CategoryResource($category);
    }
}
