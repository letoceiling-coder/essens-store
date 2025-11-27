<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Folder;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Subcategory;
use App\Services\EssensWorldParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-products {--save-images : Сохранять изображения в Media}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Проходит по всем категориям и подкатегориям, добавляет или обновляет товары';

    protected EssensWorldParser $parser;
    protected $commonFolder = null;

    /**
     * Статистика
     */
    protected $totalProducts = 0;
    protected $totalSaved = 0;
    protected $totalErrors = 0;
    protected $totalSkipped = 0;
    protected $totalImagesSaved = 0;
    protected $startTime;

    public function __construct(EssensWorldParser $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->startTime = microtime(true);
        $this->info('🚀 Запуск обновления товаров...');
        $this->newLine();

        // Проверяем авторизацию
        $this->info('Проверка авторизации...');
        $authResult = $this->parser->checkAuthentication();
        if (!$authResult['authenticated']) {
            $this->error('❌ Ошибка авторизации. Убедитесь, что парсер настроен правильно.');
            return Command::FAILURE;
        }
        $this->info('✓ Авторизация успешна');
        $this->newLine();

        // Находим или создаем папку "общая" для сохранения изображений
        if ($this->option('save-images')) {
            $this->commonFolder = Folder::where('name', 'общая')
                ->whereNull('parent_id')
                ->first();
            
            if (!$this->commonFolder) {
                $this->commonFolder = Folder::create([
                    'name' => 'общая',
                    'slug' => 'obshchaya',
                    'parent_id' => null,
                    'position' => 0,
                ]);
                $this->info('✓ Создана папка "общая" для изображений');
            }
        }

        // Получаем все категории с подкатегориями
        $categories = Category::with('subcategories')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        if ($categories->isEmpty()) {
            $this->error('❌ Категории не найдены. Запустите команду "php artisan set-categories" для синхронизации категорий.');
            return Command::FAILURE;
        }

        $this->info("Найдено категорий: {$categories->count()}");
        $totalSubcategories = $categories->sum(function ($category) {
            return $category->subcategories->where('is_active', true)->count();
        });
        $this->info("Найдено подкатегорий: {$totalSubcategories}");
        $this->newLine();

        // Проходим по каждой категории
        foreach ($categories as $category) {
            $this->info("📁 Категория: {$category->name}");
            
            $subcategories = $category->subcategories()
                ->where('is_active', true)
                ->orderBy('position')
                ->get();

            if ($subcategories->isEmpty()) {
                $this->warn("  ⚠️  У категории нет активных подкатегорий");
                $this->newLine();
                continue;
            }

            // Проходим по каждой подкатегории
            foreach ($subcategories as $subcategory) {
                $subcategoryStartTime = microtime(true);
                
                $this->line("  📂 Подкатегория: {$subcategory->name}");
                
                if (!$subcategory->external_id) {
                    $this->warn("     ⚠️  У подкатегории не указан external_id. Пропускаем.");
                    $this->newLine();
                    continue;
                }

                // Парсим товары из подкатегории
                $result = $this->parseSubcategory($subcategory);
                
                $subcategoryTime = round(microtime(true) - $subcategoryStartTime, 2);
                
                $this->line("     ✓ Товаров найдено: {$result['found']}");
                $this->line("     ✓ Сохранено/обновлено: {$result['saved']}");
                if ($result['errors'] > 0) {
                    $this->warn("     ⚠️  Ошибок: {$result['errors']}");
                }
                if ($result['skipped'] > 0) {
                    $this->line("     ⏭️  Пропущено: {$result['skipped']}");
                }
                if ($result['images_saved'] > 0) {
                    $this->line("     🖼️  Изображений сохранено: {$result['images_saved']}");
                }
                $this->line("     ⏱️  Время: {$subcategoryTime} сек.");
                $this->newLine();

                // Обновляем общую статистику
                $this->totalProducts += $result['found'];
                $this->totalSaved += $result['saved'];
                $this->totalErrors += $result['errors'];
                $this->totalSkipped += $result['skipped'];
                $this->totalImagesSaved += $result['images_saved'];
            }
        }

        // Выводим итоговую статистику
        $totalTime = round(microtime(true) - $this->startTime, 2);
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        $this->info('📊 ИТОГОВАЯ СТАТИСТИКА');
        $this->info('═══════════════════════════════════════════════════════');
        $this->info("Всего товаров найдено: {$this->totalProducts}");
        $this->info("Сохранено/обновлено: {$this->totalSaved}");
        if ($this->totalErrors > 0) {
            $this->warn("Ошибок: {$this->totalErrors}");
        }
        if ($this->totalSkipped > 0) {
            $this->line("Пропущено: {$this->totalSkipped}");
        }
        if ($this->totalImagesSaved > 0) {
            $this->info("Изображений сохранено: {$this->totalImagesSaved}");
        }
        $this->info("⏱️  Общее время выполнения: {$totalTime} сек. (" . round($totalTime / 60, 2) . " мин.)");
        $this->info('═══════════════════════════════════════════════════════');

        return Command::SUCCESS;
    }

    /**
     * Парсить товары из подкатегории
     */
    protected function parseSubcategory(Subcategory $subcategory): array
    {
        $found = 0;
        $saved = 0;
        $errors = 0;
        $skipped = 0;
        $imagesSaved = 0;

        try {
            $catId = $subcategory->external_id;

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

            $found = count($allProducts);

            if (empty($allProducts)) {
                return [
                    'found' => 0,
                    'saved' => 0,
                    'errors' => 0,
                    'skipped' => 0,
                    'images_saved' => 0,
                ];
            }

            // Парсим каждый товар и сохраняем в БД
            $progressBar = $this->output->createProgressBar(count($allProducts));
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
            $progressBar->start();
            
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
                        $skipped++;
                        continue;
                    }

                    // Извлекаем SKU из URL или данных
                    $sku = $productData['sku'] ?? $productLink['sku'] ?? null;
                    if (!$sku && preg_match('/-d(\d+)/', $productUrl, $matches)) {
                        $sku = $matches[1];
                    }

                    // Проверяем, существует ли товар с таким SKU
                    $existingProduct = Product::where('sku', $sku)->first();
                    
                    // Определяем основную цену (приоритет: discounted_price > price)
                    $mainPrice = $productData['discounted_price'] ?? $productData['price'] ?? null;
                    
                    $product = null;
                    if ($existingProduct) {
                        // Обновляем существующий товар - обновляем ВСЕ данные
                        $updateData = [
                            'subcategory_id' => $subcategory->id, // Обновляем категорию/подкатегорию
                            'name' => $productData['name'],
                            'sku' => $sku, // Обновляем SKU на случай, если он изменился
                            'description' => $productData['description'],
                            'price' => $mainPrice,
                            'old_price' => $productData['old_price'] ?? null,
                            'discounted_price' => $productData['discounted_price'] ?? null,
                            'recommended_price' => $productData['recommended_price'] ?? null,
                            'in_stock' => $productData['in_stock'] ?? true,
                            'volume' => $productData['volume'] ?? null,
                            'type' => $productData['type'] ?? $existingProduct->type,
                            'gender_target' => $productData['gender_target'] ?? $existingProduct->gender_target,
                            'tags' => $productData['tags'] ?? $existingProduct->tags,
                        ];
                        
                        $existingProduct->update($updateData);
                        $product = $existingProduct;
                        $saved++;
                    } else {
                        // Создаем новый товар
                        if (!$mainPrice) {
                            $skipped++;
                            continue; // Пропускаем товары без цены
                        }
                        
                        $product = Product::create([
                            'subcategory_id' => $subcategory->id,
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
                    }

                    // Сохраняем изображения товара
                    if ($product && !empty($productData['images'])) {
                        $newImageUrls = [];
                        
                        foreach ($productData['images'] as $index => $imageUrl) {
                            try {
                                if (empty($imageUrl)) {
                                    continue;
                                }
                                
                                $finalImageUrl = $imageUrl;
                                
                                // Если включена опция сохранения в Media, сохраняем изображение в Media
                                if ($this->option('save-images') && $this->commonFolder) {
                                    $savedImage = $this->saveImageToMedia($imageUrl, $this->commonFolder, $product->name);
                                    if ($savedImage) {
                                        $imagesSaved++;
                                        // Используем URL из Media
                                        $finalImageUrl = $savedImage->url;
                                    }
                                }
                                
                                // Всегда сохраняем изображение в ProductImage
                                ProductImage::updateOrCreate(
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
                            } catch (\Exception $e) {
                                Log::warning("Failed to save product image", [
                                    'product_id' => $product->id,
                                    'image_url' => $imageUrl,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                        
                        // Если товар существующий, удаляем изображения, которых больше нет в новых данных
                        if ($existingProduct && !empty($newImageUrls)) {
                            ProductImage::where('product_id', $product->id)
                                ->whereNotIn('url', $newImageUrls)
                                ->delete();
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error parsing product", [
                        'url' => $productUrl ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    $errors++;
                }
                
                $progressBar->advance();
            }
            
            $progressBar->finish();

        } catch (\Exception $e) {
            Log::error("Error parsing subcategory", [
                'subcategory_id' => $subcategory->id,
                'error' => $e->getMessage(),
            ]);
            $errors++;
        }

        return [
            'found' => $found,
            'saved' => $saved,
            'errors' => $errors,
            'skipped' => $skipped,
            'images_saved' => $imagesSaved,
        ];
    }

    /**
     * Сохранить изображение в Media
     */
    protected function saveImageToMedia(string $imageUrl, Folder $folder, string $productName): ?\App\Models\Media
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
                'user_id' => null,
                'temporary' => false,
                'metadata' => json_encode([
                    'path' => $relativePath,
                    'mime_type' => $mimeType,
                    'source_url' => $imageUrl,
                ]),
            ]);

            return $media;
        } catch (\Exception $e) {
            Log::error("Error saving image to Media", [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Получить путь к папке
     */
    protected function getFolderPath(Folder $folder): string
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

