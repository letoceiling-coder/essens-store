<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Essens — качественная натуральная продукция для здоровья и красоты. Широкий ассортимент товаров с гарантией качества. Доставка по всей России.">
    <meta name="keywords" content="Essens, интернет-магазин, здоровье, красота, натуральная продукция, парфюмерия, косметика">
    <meta name="author" content="Essens">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Russian">
    
    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Essens">
    <meta property="og:locale" content="ru_RU">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    
    <title>{{ config('app.name', 'Essens') }}</title>
    
    <!-- Structured Data (будет обновляться через JavaScript) -->
    <script type="application/ld+json" id="base-organization-schema">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Essens',
        'url' => config('app.url', 'https://essens-store.ru'),
        'description' => 'Интернет-магазин качественной натуральной продукции для здоровья и красоты'
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Применяем тему до загрузки страницы, чтобы избежать мигания
        (function() {
            const theme = localStorage.getItem('theme') || 'light';
            const html = document.documentElement;
            if (theme === 'dark') {
                html.classList.add('dark');
                html.setAttribute('data-theme', 'dark');
                html.style.colorScheme = 'dark';
            } else {
                html.style.colorScheme = 'light';
            }
        })();
    </script>
</head>
<body class="min-h-screen bg-background text-foreground">
    <div id="app"></div>
</body>
</html>

