<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Services\EssensWorldParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SetCategories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'set-categories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Удаляет все категории, подкатегории и товары из БД, затем загружает категории через парсер';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начинаем синхронизацию категорий...');

        // Шаг 1: Удаление всех товаров, подкатегорий и категорий
        $this->info('Шаг 1: Удаление существующих данных...');
        
        try {
            DB::beginTransaction();

            // Удаляем товары (они связаны с подкатегориями через foreign key)
            $productsCount = Product::count();
            if ($productsCount > 0) {
                $this->info("Удаление товаров: {$productsCount} записей...");
                Product::query()->delete();
                $this->info('✓ Товары удалены');
            } else {
                $this->info('✓ Товары отсутствуют');
            }

            // Удаляем подкатегории
            $subcategoriesCount = Subcategory::count();
            if ($subcategoriesCount > 0) {
                $this->info("Удаление подкатегорий: {$subcategoriesCount} записей...");
                Subcategory::query()->delete();
                $this->info('✓ Подкатегории удалены');
            } else {
                $this->info('✓ Подкатегории отсутствуют');
            }

            // Удаляем категории
            $categoriesCount = Category::count();
            if ($categoriesCount > 0) {
                $this->info("Удаление категорий: {$categoriesCount} записей...");
                Category::query()->delete();
                $this->info('✓ Категории удалены');
            } else {
                $this->info('✓ Категории отсутствуют');
            }

            DB::commit();
            $this->info('✓ Все данные успешно удалены');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ошибка при удалении данных: ' . $e->getMessage());
            $this->error('Трассировка: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        // Шаг 2: Получение категорий через парсер
        $this->info('Шаг 2: Получение категорий через парсер...');
        
        try {
            $parser = new EssensWorldParser();
            $categories = $parser->getEshopCategories();

            if (empty($categories)) {
                $this->error('Не удалось получить категории через парсер');
                return Command::FAILURE;
            }

            $this->info("Получено категорий: " . count($categories));
        } catch (\Exception $e) {
            $this->error('Ошибка при получении категорий: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // Шаг 3: Сохранение категорий и подкатегорий в БД
        $this->info('Шаг 3: Сохранение категорий и подкатегорий в БД...');

        try {
            DB::beginTransaction();

            $position = 1;
            $totalCategories = 0;
            $totalSubcategories = 0;
            $processedCategoryIds = []; // Для отслеживания уже обработанных категорий

            $bar = $this->output->createProgressBar(count($categories));
            $bar->start();

            foreach ($categories as $categoryData) {
                // Пропускаем категории без ID
                if (!isset($categoryData['id']) || !isset($categoryData['name'])) {
                    $bar->advance();
                    continue;
                }

                // Пропускаем дубликаты (если категория уже была обработана)
                if (in_array($categoryData['id'], $processedCategoryIds)) {
                    $bar->advance();
                    continue;
                }

                // Создаем категорию
                $category = Category::create([
                    'external_id' => $categoryData['id'], // Сохраняем cat_id с сайта
                    'name' => $categoryData['name'],
                    'slug' => $this->generateSlug($categoryData['name'], $categoryData['id']),
                    'is_active' => true,
                    'position' => $position++,
                ]);

                $totalCategories++;
                $processedCategoryIds[] = $categoryData['id'];

                // Если у категории есть подкатегории, сохраняем их
                if (!empty($categoryData['subcategories']) && is_array($categoryData['subcategories'])) {
                    $subPosition = 1;
                    foreach ($categoryData['subcategories'] as $subcategoryData) {
                        if (!isset($subcategoryData['id']) || !isset($subcategoryData['name'])) {
                            continue;
                        }

                        Subcategory::create([
                            'external_id' => $subcategoryData['id'], // Сохраняем cat_id с сайта
                            'category_id' => $category->id,
                            'name' => $subcategoryData['name'],
                            'slug' => $this->generateSlug($subcategoryData['name'], $subcategoryData['id']),
                            'is_active' => true,
                            'position' => $subPosition++,
                        ]);

                        $totalSubcategories++;
                    }
                }

                $bar->advance();
            }

            $bar->finish();
            $this->newLine(2);

            DB::commit();

            $this->info("✓ Успешно сохранено:");
            $this->info("  - Категорий: {$totalCategories}");
            $this->info("  - Подкатегорий: {$totalSubcategories}");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Ошибка при сохранении категорий: ' . $e->getMessage());
            $this->error('Трассировка: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        $this->info('✓ Синхронизация категорий завершена успешно!');
        return Command::SUCCESS;
    }

    /**
     * Генерирует slug из названия и ID
     */
    protected function generateSlug(string $name, int $id): string
    {
        // Создаем slug из названия
        $slug = Str::slug($name);
        
        // Если slug пустой, используем ID
        if (empty($slug)) {
            $slug = 'category-' . $id;
        }

        // Добавляем ID для уникальности, если нужно
        // Можно убрать, если slug уже уникальный
        return $slug;
    }
}

