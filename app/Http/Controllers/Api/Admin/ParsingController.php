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

    /**
     * Получить категории с eshop.php
     */
    public function getEshopCategories()
    {
        try {
            $categories = $this->parser->getEshopCategories();

            // Если категории не найдены, возвращаем более подробную информацию для отладки
            if (count($categories) === 0) {
                \Log::warning("No eshop categories found", [
                    'method' => 'getEshopCategories',
                ]);
                
                return response()->json([
                    'success' => false,
                    'data' => [],
                    'count' => 0,
                    'message' => 'Категории не найдены. Проверьте логи сервера для получения дополнительной информации.',
                    'hint' => 'Возможно, структура страницы изменилась или требуется авторизация. Проверьте, что авторизация прошла успешно.',
                ], 200); // Возвращаем 200, но с success: false
            }

            return response()->json([
                'success' => true,
                'data' => $categories,
                'count' => count($categories),
            ]);
        } catch (\Exception $e) {
            \Log::error("Exception in getEshopCategories", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении категорий: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить товары из категории через eshop.php
     */
    public function getProductsFromEshopCategory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cat_id' => 'required|integer|min:1',
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
            $products = $this->parser->getProductsFromEshopCategory(
                $request->cat_id,
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
     * Получить HTML страницы для отладки (только для разработки)
     */
    public function getEshopPageHtml()
    {
        try {
            // Используем рефлексию для доступа к protected методу fetchPage
            $reflection = new \ReflectionClass($this->parser);
            $method = $reflection->getMethod('fetchPage');
            $method->setAccessible(true);
            
            $html = $method->invoke($this->parser, 'https://www.essensworld.ru/eshop/');
            
            if (!$html) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить HTML страницы',
                ], 404);
            }

            // Возвращаем только первые 50000 символов для безопасности
            $htmlPreview = substr($html, 0, 50000);
            
            return response()->json([
                'success' => true,
                'html_length' => strlen($html),
                'html_preview' => $htmlPreview,
                'note' => 'Показаны первые 50000 символов HTML',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Парсить все товары из категории/подкатегории и сохранить в БД
     */
    public function parseAndSaveProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|integer|exists:categories,id',
            'subcategory_id' => 'nullable|integer|exists:subcategories,id',
            'cat_id' => 'nullable|integer|min:1', // cat_id с сайта essensworld.ru
            'save_images' => 'nullable|boolean', // Сохранять ли изображения в Media
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Определяем cat_id для парсинга
            $catId = null;
            $subcategoryId = $request->subcategory_id;
            
            if ($request->cat_id) {
                // Если указан cat_id напрямую, используем его
                $catId = $request->cat_id;
            } elseif ($subcategoryId) {
                // Если передан subcategory_id, получаем external_id (cat_id с сайта)
                $subcategory = \App\Models\Subcategory::find($subcategoryId);
                if ($subcategory && $subcategory->external_id) {
                    $catId = $subcategory->external_id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'У подкатегории не указан external_id (cat_id с сайта). Запустите команду set-categories для синхронизации.',
                    ], 422);
                }
            } elseif ($request->category_id) {
                // Если передан только category_id, используем external_id категории
                $category = \App\Models\Category::find($request->category_id);
                if ($category && $category->external_id) {
                    $catId = $category->external_id;
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'У категории не указан external_id (cat_id с сайта). Запустите команду set-categories для синхронизации.',
                    ], 422);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Необходимо указать category_id, subcategory_id или cat_id',
                ], 422);
            }

            // Получаем все ссылки на товары из категории
            $allProducts = [];
            $page = 1;
            $maxPages = 100; // Ограничение для безопасности
            
            do {
                $products = $this->parser->getProductsFromEshopCategory($catId, $page);
                
                if (empty($products)) {
                    break;
                }
                
                $allProducts = array_merge($allProducts, $products);
                $page++;
                
                // Ограничение по страницам
                if ($page > $maxPages) {
                    break;
                }
                
                // Если товаров меньше ожидаемого, возможно это последняя страница
                if (count($products) < 20) {
                    break;
                }
            } while (true);

            if (empty($allProducts)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Товары не найдены в указанной категории',
                ], 404);
            }

            // Находим или создаем папку "общая" для сохранения изображений
            $commonFolder = null;
            if ($request->save_images) {
                $commonFolder = \App\Models\Folder::where('name', 'общая')
                    ->whereNull('parent_id')
                    ->first();
                
                if (!$commonFolder) {
                    // Создаем папку "общая"
                    $commonFolder = \App\Models\Folder::create([
                        'name' => 'общая',
                        'slug' => 'obshchaya',
                        'parent_id' => null,
                        'position' => 0,
                    ]);
                }
            }

            // Парсим каждый товар и сохраняем в БД
            $saved = 0;
            $errors = 0;
            $skipped = 0;
            $imagesSaved = 0;
            
            foreach ($allProducts as $productLink) {
                try {
                    $productUrl = $productLink['url'] ?? null;
                    if (!$productUrl) {
                        $skipped++;
                        continue;
                    }

                    // Парсим страницу товара
                    $productData = $this->parser->parseProduct($productUrl);
                    
                    if (!$productData || !$productData['name']) {
                        \Log::warning("Product parsing failed or no name", [
                            'url' => $productUrl,
                            'has_data' => !empty($productData),
                        ]);
                        $skipped++;
                        continue;
                    }
                    
                    // Логируем информацию об изображениях
                    \Log::info("Product parsed", [
                        'name' => $productData['name'] ?? 'unknown',
                        'images_count' => count($productData['images'] ?? []),
                        'has_images' => !empty($productData['images']),
                    ]);

                    // Определяем subcategory_id
                    $finalSubcategoryId = $subcategoryId;
                    if (!$finalSubcategoryId && $request->category_id) {
                        // Если передан только category_id, берем первую подкатегорию категории
                        $category = \App\Models\Category::with('subcategories')->find($request->category_id);
                        if ($category && $category->subcategories->count() > 0) {
                            $finalSubcategoryId = $category->subcategories->first()->id;
                        } else {
                            // Если у категории нет подкатегорий, пропускаем товар
                            \Log::warning("Category has no subcategories", [
                                'category_id' => $request->category_id,
                                'product_url' => $productUrl,
                            ]);
                            $skipped++;
                            continue;
                        }
                    }

                    if (!$finalSubcategoryId) {
                        \Log::warning("No subcategory_id determined", [
                            'category_id' => $request->category_id,
                            'subcategory_id' => $subcategoryId,
                            'cat_id' => $request->cat_id,
                            'product_url' => $productUrl,
                        ]);
                        $skipped++;
                        continue;
                    }

                    // Извлекаем SKU из URL или данных
                    $sku = $productData['sku'] ?? $productLink['sku'] ?? null;
                    if (!$sku && preg_match('/-d(\d+)/', $productUrl, $matches)) {
                        $sku = $matches[1];
                    }

                    // Проверяем, существует ли товар с таким SKU
                    $existingProduct = \App\Models\Product::where('sku', $sku)->first();
                    
                    // Определяем основную цену (приоритет: discounted_price > price)
                    $mainPrice = $productData['discounted_price'] ?? $productData['price'] ?? null;
                    
                    $product = null;
                    if ($existingProduct) {
                        // Обновляем существующий товар - обновляем ВСЕ данные
                        $updateData = [
                            'subcategory_id' => $finalSubcategoryId, // Обновляем категорию/подкатегорию
                            'name' => $productData['name'],
                            'sku' => $sku, // Обновляем SKU на случай, если он изменился
                            'description' => $productData['description'],
                            'price' => $mainPrice,
                            'old_price' => $productData['old_price'] ?? null,
                            'discounted_price' => $productData['discounted_price'] ?? null,
                            'recommended_price' => $productData['recommended_price'] ?? null,
                            'in_stock' => $productData['in_stock'] ?? true,
                            'volume' => $productData['volume'] ?? null,
                            'type' => $productData['type'] ?? $existingProduct->type, // Сохраняем существующий, если не парсится
                            'gender_target' => $productData['gender_target'] ?? $existingProduct->gender_target, // Сохраняем существующий, если не парсится
                            'tags' => $productData['tags'] ?? $existingProduct->tags, // Сохраняем существующие, если не парсятся
                        ];
                        
                        $existingProduct->update($updateData);
                        $product = $existingProduct;
                        $saved++;
                        
                        \Log::info("Existing product updated with all data", [
                            'product_id' => $product->id,
                            'sku' => $sku,
                            'subcategory_id' => $finalSubcategoryId,
                            'has_images_in_data' => !empty($productData['images']),
                            'updated_fields' => array_keys($updateData),
                        ]);
                    } else {
                        // Создаем новый товар
                        if (!$mainPrice) {
                            $skipped++;
                            continue; // Пропускаем товары без цены
                        }
                        
                        $product = \App\Models\Product::create([
                            'subcategory_id' => $finalSubcategoryId,
                            'name' => $productData['name'],
                            'sku' => $sku,
                            'type' => $productData['type'] ?? null,
                            'gender_target' => $productData['gender_target'] ?? null,
                            'description' => $productData['description'],
                            'price' => $mainPrice,
                            'old_price' => $productData['old_price'] ?? null,
                            'discounted_price' => $productData['discounted_price'] ?? null,
                            'recommended_price' => $productData['recommended_price'] ?? null,
                            'currency' => 'RUB',
                            'in_stock' => $productData['in_stock'] ?? true,
                            'volume' => $productData['volume'] ?? null,
                            'tags' => $productData['tags'] ?? null,
                        ]);
                        $saved++;
                        
                        \Log::info("New product created", [
                            'product_id' => $product->id,
                            'sku' => $sku,
                            'subcategory_id' => $finalSubcategoryId,
                        ]);
                    }

                    // Сохраняем изображения товара
                    if ($product) {
                        if (empty($productData['images'])) {
                            \Log::warning("Product has no images", [
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'product_url' => $productUrl,
                            ]);
                        } else {
                            \Log::info("Saving product images", [
                                'product_id' => $product->id,
                                'images_count' => count($productData['images']),
                            ]);
                            
                            // Если товар существующий, собираем новые URL изображений для сравнения
                            $newImageUrls = [];
                            
                            foreach ($productData['images'] as $index => $imageUrl) {
                                try {
                                    if (empty($imageUrl)) {
                                        \Log::warning("Empty image URL", [
                                            'product_id' => $product->id,
                                            'index' => $index,
                                        ]);
                                        continue;
                                    }
                                    
                                    $finalImageUrl = $imageUrl;
                                    
                                    // Если включена опция сохранения в Media, сохраняем изображение в Media
                                    if ($request->save_images && $commonFolder) {
                                        $savedImage = $this->saveImageToMedia($imageUrl, $commonFolder, $product->name);
                                        if ($savedImage) {
                                            $imagesSaved++;
                                            // Используем URL из Media
                                            $finalImageUrl = $savedImage->url;
                                            \Log::info("Image saved to Media", [
                                                'product_id' => $product->id,
                                                'original_url' => $imageUrl,
                                                'media_url' => $finalImageUrl,
                                            ]);
                                        } else {
                                            \Log::warning("Failed to save image to Media, using original URL", [
                                                'product_id' => $product->id,
                                                'image_url' => $imageUrl,
                                            ]);
                                        }
                                    }
                                    
                                    // Всегда сохраняем изображение в ProductImage (либо URL из парсера, либо из Media)
                                    $productImage = \App\Models\ProductImage::updateOrCreate(
                                        [
                                            'product_id' => $product->id,
                                            'url' => $finalImageUrl,
                                        ],
                                        [
                                            'order' => $index,
                                            'is_primary' => $index === 0,
                                        ]
                                    );
                                    
                                    $newImageUrls[] = $finalImageUrl;
                                    
                                    $newImageUrls[] = $finalImageUrl;
                                    
                                    \Log::info("ProductImage saved", [
                                        'product_image_id' => $productImage->id,
                                        'product_id' => $product->id,
                                        'url' => $finalImageUrl,
                                        'is_primary' => $index === 0,
                                    ]);
                                } catch (\Exception $e) {
                                    \Log::error("Failed to save product image", [
                                        'product_id' => $product->id,
                                        'image_url' => $imageUrl,
                                        'error' => $e->getMessage(),
                                        'trace' => $e->getTraceAsString(),
                                    ]);
                                }
                            }
                            
                            // Если товар существующий, удаляем изображения, которых больше нет в новых данных
                            if ($existingProduct && !empty($newImageUrls)) {
                                $deletedCount = \App\Models\ProductImage::where('product_id', $product->id)
                                    ->whereNotIn('url', $newImageUrls)
                                    ->delete();
                                
                                if ($deletedCount > 0) {
                                    \Log::info("Deleted old product images", [
                                        'product_id' => $product->id,
                                        'deleted_count' => $deletedCount,
                                    ]);
                                }
                            }
                        }
                    } else {
                        \Log::warning("Product is null, cannot save images", [
                            'product_url' => $productUrl,
                        ]);
                    }
                } catch (\Exception $e) {
                    \Log::error("Error parsing product", [
                        'url' => $productUrl ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Парсинг завершен',
                'data' => [
                    'total_found' => count($allProducts),
                    'saved' => $saved,
                    'errors' => $errors,
                    'skipped' => $skipped,
                    'images_saved' => $imagesSaved,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error("Exception in parseAndSaveProducts", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при парсинге товаров: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Сохранить изображение в Media
     */
    protected function saveImageToMedia(string $imageUrl, \App\Models\Folder $folder, string $productName): ?\App\Models\Media
    {
        try {
            // Получаем CookieJar через рефлексию (метод protected)
            $reflection = new \ReflectionClass($this->parser);
            $method = $reflection->getMethod('getCookieJar');
            $method->setAccessible(true);
            $cookieJar = $method->invoke($this->parser);
            
            // Скачиваем изображение
            $response = \Illuminate\Support\Facades\Http::timeout(30)
                ->withOptions([
                    'cookies' => $cookieJar,
                    'verify' => false,
                    'allow_redirects' => true,
                ])
                ->get($imageUrl);

            if (!$response->successful()) {
                \Log::warning("Failed to download image", [
                    'url' => $imageUrl,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $imageContent = $response->body();
            if (empty($imageContent)) {
                return null;
            }

            // Определяем расширение файла
            $extension = 'jpg';
            $contentType = $response->header('Content-Type');
            if ($contentType) {
                if (str_contains($contentType, 'png')) {
                    $extension = 'png';
                } elseif (str_contains($contentType, 'gif')) {
                    $extension = 'gif';
                } elseif (str_contains($contentType, 'webp')) {
                    $extension = 'webp';
                }
            } else {
                // Пробуем определить по URL
                $path = parse_url($imageUrl, PHP_URL_PATH);
                if ($path && preg_match('/\.([a-z]+)$/i', $path, $matches)) {
                    $extension = strtolower($matches[1]);
                }
            }

            // Генерируем уникальное имя файла
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $originalName = \Illuminate\Support\Str::slug($productName) . '.' . $extension;

            // Получаем путь папки
            $folderPath = $this->getFolderPath($folder);
            $uploadPath = 'upload/' . $folderPath;

            // Создаем директорию если не существует
            $fullPath = public_path($uploadPath);
            if (!file_exists($fullPath)) {
                mkdir($fullPath, 0755, true);
            }

            // Сохраняем файл
            $filePath = $fullPath . '/' . $fileName;
            file_put_contents($filePath, $imageContent);
            $relativePath = $uploadPath . '/' . $fileName;

            // Получаем размеры изображения
            $width = null;
            $height = null;
            $imageInfo = @getimagesize($filePath);
            if ($imageInfo !== false) {
                $width = $imageInfo[0];
                $height = $imageInfo[1];
            }

            // Определяем MIME тип
            $mimeType = $contentType ?: ($imageInfo ? $imageInfo['mime'] : 'image/jpeg');

            // Сохраняем в БД
            $media = \App\Models\Media::create([
                'name' => $fileName,
                'original_name' => $originalName,
                'extension' => $extension,
                'disk' => $uploadPath,
                'width' => $width,
                'height' => $height,
                'type' => 'photo',
                'size' => filesize($filePath),
                'folder_id' => $folder->id,
                'user_id' => auth()->check() ? auth()->id() : null,
                'temporary' => false,
                'metadata' => json_encode([
                    'path' => $relativePath,
                    'mime_type' => $mimeType,
                    'source_url' => $imageUrl,
                ]),
            ]);

            return $media;
        } catch (\Exception $e) {
            \Log::error("Error saving image to Media", [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Получить путь к папке (копия метода из MediaController)
     */
    protected function getFolderPath(\App\Models\Folder $folder): string
    {
        $path = [];
        $currentFolder = $folder;

        // Загружаем родителей для построения пути
        while ($currentFolder) {
            array_unshift($path, \Illuminate\Support\Str::slug($currentFolder->name));
            $currentFolder = $currentFolder->parent;
        }

        return implode('/', $path);
    }
}

