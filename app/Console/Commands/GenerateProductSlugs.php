<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateProductSlugs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:generate-slugs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует slug для всех товаров, у которых его нет';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Генерация slug для товаров...');

        $products = Product::whereNull('slug')
            ->orWhere('slug', '')
            ->get();

        if ($products->isEmpty()) {
            $this->info('Все товары уже имеют slug.');
            return Command::SUCCESS;
        }

        $this->info("Найдено товаров без slug: {$products->count()}");

        $progressBar = $this->output->createProgressBar($products->count());
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
        $progressBar->start();

        $generated = 0;
        $errors = 0;

        foreach ($products as $product) {
            try {
                $progressBar->setMessage("Обработка: {$product->name}");

                // Генерируем slug из названия
                $slug = $this->generateUniqueSlug($product->name, $product->id);

                $product->slug = $slug;
                $product->save();

                $generated++;
            } catch (\Exception $e) {
                $this->error("\nОшибка при обработке товара ID {$product->id}: {$e->getMessage()}");
                $errors++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info("✓ Сгенерировано slug: {$generated}");
        if ($errors > 0) {
            $this->warn("⚠ Ошибок: {$errors}");
        }

        return Command::SUCCESS;
    }

    /**
     * Generate a unique slug from the product name.
     */
    protected function generateUniqueSlug(string $name, ?int $excludeId = null): string
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (Product::where('slug', $slug)
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}

