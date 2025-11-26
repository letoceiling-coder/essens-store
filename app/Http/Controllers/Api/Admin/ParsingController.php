<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\EssensWorldParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ParsingController extends Controller
{
    protected $parser;

    public function __construct(EssensWorldParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Проверить доступность сайта
     */
    public function checkAvailability()
    {
        $result = $this->parser->checkAvailability();

        return response()->json([
            'success' => $result['available'] ?? false,
            'data' => $result,
        ]);
    }

    /**
     * Проверить авторизацию
     */
    public function checkAuthentication()
    {
        try {
            $result = $this->parser->checkAuthentication();

            return response()->json([
                'success' => $result['authenticated'] ?? false,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке авторизации: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить категории
     */
    public function getCategories()
    {
        try {
            $categories = $this->parser->getCategories();

            return response()->json([
                'success' => true,
                'data' => $categories,
                'count' => count($categories),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении категорий: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить товары из категории
     */
    public function getProductsFromCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_url' => 'required|url',
            'page' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $products = $this->parser->getProductsFromCategory(
                $request->category_url,
                $request->get('page', 1)
            );

            return response()->json([
                'success' => true,
                'data' => $products,
                'count' => count($products),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении товаров: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Парсинг товара по прямой ссылке
     */
    public function parseProduct(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = $this->parser->parseProduct($request->url);

            if (!$product) {
                \Log::error("Failed to parse product", [
                    'url' => $request->url,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить данные товара. Проверьте URL и убедитесь, что авторизация прошла успешно.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        } catch (\Exception $e) {
            \Log::error("Exception in parseProduct controller", [
                'url' => $request->url,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при парсинге товара: ' . $e->getMessage(),
            ], 500);
        }
    }
}

