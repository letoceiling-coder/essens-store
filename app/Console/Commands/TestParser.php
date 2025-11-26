<?php

namespace App\Console\Commands;

use App\Services\EssensWorldParser;
use Illuminate\Console\Command;

class TestParser extends Command
{
    protected $signature = 'parser:test {url?}';
    protected $description = 'Тестирование парсера EssensWorld';

    protected $parser;

    public function __construct(EssensWorldParser $parser)
    {
        parent::__construct();
        $this->parser = $parser;
    }

    public function handle()
    {
        $this->info('🧪 Тестирование парсера EssensWorld');
        $this->newLine();

        // 1. Проверка доступности
        $this->info('1. Проверка доступности сайта...');
        $availability = $this->parser->checkAvailability();
        if ($availability['available']) {
            $this->info("   ✓ Сайт доступен (HTTP {$availability['status_code']})");
        } else {
            $this->error("   ✗ Сайт недоступен");
            if (isset($availability['error'])) {
                $this->error("   Ошибка: {$availability['error']}");
            }
            return 1;
        }
        $this->newLine();

        // 2. Получение категорий
        $this->info('2. Получение категорий...');
        $categories = $this->parser->getCategories();
        if (count($categories) > 0) {
            $this->info("   ✓ Найдено категорий: " . count($categories));
            foreach (array_slice($categories, 0, 5) as $category) {
                $this->line("   - {$category['name']}");
            }
            if (count($categories) > 5) {
                $this->line("   ... и еще " . (count($categories) - 5) . " категорий");
            }
        } else {
            $this->warn("   ⚠ Категории не найдены (возможно, нужно обновить селекторы)");
        }
        $this->newLine();

        // 3. Парсинг товара
        $testUrl = $this->argument('url') ?: 'https://www.essensworld.ru/gely-dlya-dusha-colostrum-d163684/';
        $this->info("3. Парсинг товара: {$testUrl}");
        $product = $this->parser->parseProduct($testUrl);
        
        if ($product) {
            $this->info("   ✓ Товар успешно распарсен");
            $this->line("   Название: " . ($product['name'] ?: 'не найдено'));
            $this->line("   Цена: " . ($product['price'] ?: 'не найдена'));
            $this->line("   Артикул: " . ($product['sku'] ?: 'не найден'));
            $this->line("   В наличии: " . ($product['in_stock'] ? 'Да' : 'Нет'));
            $this->line("   Изображений: " . count($product['images']));
            if ($product['description']) {
                $desc = mb_substr($product['description'], 0, 100);
                $this->line("   Описание: {$desc}...");
            }
        } else {
            $this->error("   ✗ Не удалось распарсить товар");
            $this->warn("   ⚠ Возможно, нужно обновить селекторы под структуру сайта");
        }

        $this->newLine();
        $this->info('✅ Тестирование завершено');
        
        return 0;
    }
}

