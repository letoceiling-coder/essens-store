/**
 * SEO Utility для управления мета-тегами и микроразметкой
 */

/**
 * Устанавливает мета-теги для страницы
 */
export function setMetaTags({
    title = 'Essens — Интернет-магазин продукции для здоровья и красоты',
    description = 'Essens — качественная натуральная продукция для здоровья и красоты. Широкий ассортимент товаров с гарантией качества.',
    keywords = '',
    image = '',
    url = '',
    type = 'website',
    siteName = 'Essens',
}) {
    // Title
    document.title = title;

    // Базовые мета-теги
    setMetaTag('description', description);
    if (keywords) {
        setMetaTag('keywords', keywords);
    }

    // Open Graph
    setMetaTag('og:title', title, 'property');
    setMetaTag('og:description', description, 'property');
    setMetaTag('og:type', type, 'property');
    setMetaTag('og:site_name', siteName, 'property');
    if (url) {
        setMetaTag('og:url', url, 'property');
    }
    if (image) {
        setMetaTag('og:image', image, 'property');
        setMetaTag('og:image:width', '1200', 'property');
        setMetaTag('og:image:height', '630', 'property');
    }

    // Twitter Card
    setMetaTag('twitter:card', 'summary_large_image');
    setMetaTag('twitter:title', title);
    setMetaTag('twitter:description', description);
    if (image) {
        setMetaTag('twitter:image', image);
    }

    // Canonical URL
    if (url) {
        setCanonicalUrl(url);
    }
}

/**
 * Устанавливает или обновляет мета-тег
 */
function setMetaTag(name, content, attribute = 'name') {
    let meta = document.querySelector(`meta[${attribute}="${name}"]`);
    
    if (!meta) {
        meta = document.createElement('meta');
        meta.setAttribute(attribute, name);
        document.head.appendChild(meta);
    }
    
    meta.setAttribute('content', content);
}

/**
 * Устанавливает canonical URL
 */
function setCanonicalUrl(url) {
    let canonical = document.querySelector('link[rel="canonical"]');
    
    if (!canonical) {
        canonical = document.createElement('link');
        canonical.setAttribute('rel', 'canonical');
        document.head.appendChild(canonical);
    }
    
    canonical.setAttribute('href', url);
}

/**
 * Добавляет JSON-LD структурированные данные
 */
export function addStructuredData(data) {
    // Удаляем старые данные с таким же типом
    const existing = document.querySelector(`script[type="application/ld+json"][data-schema-type="${data['@type']}"]`);
    if (existing) {
        existing.remove();
    }

    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.setAttribute('data-schema-type', data['@type']);
    script.textContent = JSON.stringify(data);
    document.head.appendChild(script);
}

/**
 * Генерирует структурированные данные для товара
 */
export function generateProductSchema(product, baseUrl) {
    const imageUrl = product.primary_image?.url || product.images?.[0]?.url || '';
    const fullImageUrl = imageUrl.startsWith('http') ? imageUrl : `${baseUrl}${imageUrl}`;
    const productUrl = `${baseUrl}/product/${product.slug || product.id}`;
    
    const schema = {
        '@context': 'https://schema.org',
        '@type': 'Product',
        name: product.name,
        description: product.description || product.name,
        image: fullImageUrl ? [fullImageUrl] : [],
        sku: product.sku || product.id.toString(),
        mpn: product.sku || product.id.toString(),
        brand: {
            '@type': 'Brand',
            name: 'Essens',
        },
        offers: {
            '@type': 'Offer',
            url: productUrl,
            priceCurrency: product.currency || 'RUB',
            price: product.recommended_price || product.discounted_price || product.price || 0,
            availability: product.in_stock 
                ? 'https://schema.org/InStock' 
                : 'https://schema.org/OutOfStock',
            priceValidUntil: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().split('T')[0], // +1 год
        },
    };

    // Добавляем старую цену, если есть
    if (product.old_price && (product.recommended_price || product.discounted_price)) {
        schema.offers.priceSpecification = {
            '@type': 'UnitPriceSpecification',
            price: product.recommended_price || product.discounted_price,
            priceCurrency: product.currency || 'RUB',
            referenceQuantity: {
                '@type': 'QuantitativeValue',
                value: 1,
                unitCode: 'C62', // единица товара
            },
        };
    }

    // Добавляем категорию
    if (product.subcategory?.category) {
        schema.category = product.subcategory.category.name;
    }

    return schema;
}

/**
 * Генерирует структурированные данные для хлебных крошек
 */
export function generateBreadcrumbSchema(items, baseUrl) {
    return {
        '@context': 'https://schema.org',
        '@type': 'BreadcrumbList',
        itemListElement: items.map((item, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            name: item.name,
            item: item.url ? `${baseUrl}${item.url}` : undefined,
        })),
    };
}

/**
 * Генерирует структурированные данные для организации
 */
export function generateOrganizationSchema(baseUrl) {
    return {
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'Essens',
        url: baseUrl,
        logo: `${baseUrl}/logo.png`, // Замените на реальный путь к логотипу
        description: 'Интернет-магазин качественной натуральной продукции для здоровья и красоты',
        contactPoint: {
            '@type': 'ContactPoint',
            contactType: 'customer service',
            availableLanguage: ['Russian'],
        },
        sameAs: [
            // Добавьте ссылки на социальные сети, если есть
        ],
    };
}

/**
 * Генерирует структурированные данные для веб-сайта
 */
export function generateWebSiteSchema(baseUrl) {
    return {
        '@context': 'https://schema.org',
        '@type': 'WebSite',
        name: 'Essens',
        url: baseUrl,
        potentialAction: {
            '@type': 'SearchAction',
            target: {
                '@type': 'EntryPoint',
                urlTemplate: `${baseUrl}/catalog?search={search_term_string}`,
            },
            'query-input': 'required name=search_term_string',
        },
    };
}

/**
 * Получает базовый URL сайта
 */
export function getBaseUrl() {
    return window.location.origin;
}

