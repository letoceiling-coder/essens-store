# Seeders для тестирования

## Описание

Созданы сидеры для заполнения базы данных тестовыми данными для всех моделей интернет-магазина Essens.

## Структура сидеров

1. **CategorySeeder** - создает 5 основных категорий:
   - Beauty
   - Health
   - Home
   - Personal Care
   - Supplements

2. **SubcategorySeeder** - создает подкатегории для каждой категории:
   - Beauty: Aloe Vera, Colostrum, Must Have Edition, Perfumes, Creams
   - Health: Vitamins, Immune Support, Digestive Health
   - Home: Cleaning Products, Air Fresheners
   - Personal Care: Body Care, Hair Care, Face Care
   - Supplements: Protein, Omega-3

3. **ProductSeeder** - создает 12 тестовых товаров с различными типами:
   - Парфюмы (perfume)
   - Кремы (cream)
   - БАДы (supplement)
   - Чистящие средства (cleaning)
   - Спреи (spray)

4. **ProductImageSeeder** - добавляет изображения к товарам:
   - Основное изображение для каждого товара
   - Дополнительные изображения для некоторых товаров

5. **ProductVariantSeeder** - создает варианты товаров:
   - Для парфюмов, кремов, спреев: 10 мл, основной объем, 100 мл
   - Для БАДов: 30 шт, основной объем, 120 шт

6. **PromotionSeeder** - создает 4 промо-акции:
   - Новогодняя распродажа (активна)
   - Летняя акция (неактивна)
   - Новинки недели (активна)
   - Детские товары (активна)

## Использование

### Запуск всех сидеров:
```bash
php artisan db:seed
```

### Запуск конкретного сидера:
```bash
php artisan db:seed --class=CategorySeeder
php artisan db:seed --class=ProductSeeder
```

### Сброс и повторное заполнение:
```bash
php artisan migrate:fresh --seed
```

## Порядок выполнения

Сидеры выполняются в следующем порядке (важно для зависимостей):
1. CategorySeeder
2. SubcategorySeeder (зависит от Category)
3. ProductSeeder (зависит от Subcategory)
4. ProductImageSeeder (зависит от Product)
5. ProductVariantSeeder (зависит от Product)
6. PromotionSeeder (зависит от Product)

## Тестовые данные

- **Категории**: 5
- **Подкатегории**: 15
- **Товары**: 12
- **Изображения**: ~18 (минимум 1 на товар, некоторые имеют 2-3)
- **Варианты товаров**: ~30 (зависит от типа товара)
- **Промо-акции**: 4

## Примечания

- Изображения используют placeholder URLs. В продакшене их нужно заменить на реальные URL из медиа-библиотеки.
- Цены указаны в рублях (RUB).
- Некоторые товары имеют теги для фильтрации и поиска.
- Промо-акции автоматически привязываются к соответствующим товарам по тегам.


