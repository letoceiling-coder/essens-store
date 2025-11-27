<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Cookie\CookieJar;
use DOMDocument;
use DOMXPath;

class EssensWorldParser
{
    protected $baseUrl = 'https://www.essensworld.ru';
    protected $timeout = 30;
    protected $cookieJar = null;
    protected $isAuthenticated = false;
    protected $credentials = [
        'con_nr' => '952049419',
        'pass' => 'Kucaevasveta19!',
    ];

    /**
     * Инициализировать CookieJar для сохранения cookies
     */
    protected function getCookieJar()
    {
        if ($this->cookieJar === null) {
            $this->cookieJar = new CookieJar();
        }
        return $this->cookieJar;
    }

    /**
     * Получить HTTP клиент с поддержкой cookies
     */
    protected function getClient()
    {
        $cookieJar = $this->getCookieJar();
        
        // Используем более простую конфигурацию для избежания проблем с stream
        return Http::withoutVerifying() // Отключаем проверку SSL
            ->timeout($this->timeout)
            ->connectTimeout(15) // Увеличиваем таймаут подключения
            ->withOptions([
                'cookies' => $cookieJar,
                'verify' => false,
                'allow_redirects' => true, // Упрощаем настройки редиректов
                'http_errors' => false,
                'decode_content' => true, // Включаем декодирование контента
            ])
            ->retry(1, 500) // Повторять запрос 1 раз с задержкой 500ms
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ]);
    }

    /**
     * Авторизация на сайте
     */
    public function authenticate(): bool
    {
        if ($this->isAuthenticated) {
            return true;
        }

        try {
            $loginUrl = $this->baseUrl . '/login.php';
            
            // Сначала получаем страницу логина для получения возможных токенов/сессий
            try {
                $loginPage = $this->getClient()->get($loginUrl);
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                Log::error("Connection exception while loading login page", [
                    'url' => $loginUrl,
                    'message' => $e->getMessage(),
                ]);
                return false;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("Request exception while loading login page", [
                    'url' => $loginUrl,
                    'message' => $e->getMessage(),
                    'has_response' => $e->hasResponse(),
                ]);
                // Если есть ответ, попробуем продолжить
                if (!$e->hasResponse()) {
                    return false;
                }
                // Создаем response из exception
                try {
                    $loginPage = \Illuminate\Support\Facades\Http::response($e->getResponse());
                } catch (\Exception $ex) {
                    Log::error("Failed to create response from exception", [
                        'message' => $ex->getMessage(),
                    ]);
                    return false;
                }
            } catch (\Exception $e) {
                Log::error("General exception while loading login page", [
                    'url' => $loginUrl,
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                ]);
                return false;
            }
            
            if (!$loginPage->successful()) {
                Log::error("Failed to load login page", [
                    'status' => $loginPage->status(),
                    'body_preview' => substr($loginPage->body(), 0, 200),
                ]);
                return false;
            }

            // Парсим HTML для поиска возможных CSRF токенов или скрытых полей
            $html = $loginPage->body();
            $xpath = $this->parseHtml($html);
            
            // Ищем скрытые поля формы
            $hiddenInputs = [];
            $hiddenNodes = $xpath->query("//input[@type='hidden']");
            foreach ($hiddenNodes as $node) {
                $name = $node->getAttribute('name');
                $value = $node->getAttribute('value');
                if ($name && $value) {
                    $hiddenInputs[$name] = $value;
                }
            }

            // Ищем action формы (может быть другой URL)
            $formAction = $loginUrl;
            $formNode = $xpath->query("//form")->item(0);
            if ($formNode) {
                $action = $formNode->getAttribute('action');
                if ($action) {
                    if (str_starts_with($action, 'http')) {
                        $formAction = $action;
                    } else {
                        $formAction = $this->baseUrl . '/' . ltrim($action, '/');
                    }
                }
            }

            // Подготавливаем данные для отправки
            $formData = array_merge($hiddenInputs, [
                'con_nr' => $this->credentials['con_nr'],
                'pass' => $this->credentials['pass'],
            ]);

            Log::info("Attempting authentication", [
                'form_action' => $formAction,
                'form_data_keys' => array_keys($formData),
            ]);

            // Выполняем авторизацию
            $response = $this->getClient()
                ->asForm()
                ->withHeaders([
                    'Referer' => $loginUrl,
                    'Origin' => $this->baseUrl,
                ])
                ->post($formAction, $formData);

            // Проверяем успешность авторизации
            // Обычно после успешной авторизации происходит редирект или меняется содержимое страницы
            $statusCode = $response->status();
            $body = $response->body();
            
            // Проверяем различные признаки успешной авторизации
            $hasLoginForm = stripos($body, 'войти') !== false || 
                           stripos($body, 'login') !== false || 
                           stripos($body, 'Вход') !== false ||
                           stripos($body, 'Do you want to buy it?') !== false;
            $hasLogoutButton = stripos($body, 'Выйти') !== false || 
                              stripos($body, 'Logout') !== false ||
                              stripos($body, 'logout') !== false;
            $hasMyEssens = stripos($body, 'myESSENS') !== false || 
                          stripos($body, 'myessens') !== false;
            $hasProductContent = stripos($body, 'product-h') !== false || 
                               stripos($body, 'detail-photo') !== false;
            
            // Проверяем наличие данных пользователя в JavaScript (признак успешной авторизации)
            $hasUserData = preg_match('/"user_id"\s*:\s*"952049419"/', $body) !== false ||
                          preg_match('/"event"\s*:\s*"login"/', $body) !== false ||
                          stripos($body, '"user_id"') !== false;
            
            // Проверяем реальные ошибки авторизации (не просто слово "error" в коде)
            $hasAuthError = stripos($body, 'неверный пароль') !== false ||
                           stripos($body, 'неправильный пароль') !== false ||
                           stripos($body, 'неверный логин') !== false ||
                           stripos($body, 'неправильный логин') !== false ||
                           (stripos($body, 'error') !== false && stripos($body, 'login') !== false && stripos($body, 'user_id') === false);
            
            // Проверяем заголовки редиректа
            $location = $response->header('Location');
            $isRedirect = $statusCode >= 300 && $statusCode < 400;
            $redirectsToLogin = $location && stripos($location, 'login') !== false;
            
            Log::info("Authentication response analysis", [
                'status' => $statusCode,
                'is_redirect' => $isRedirect,
                'location' => $location,
                'redirects_to_login' => $redirectsToLogin,
                'has_login_form' => $hasLoginForm,
                'has_logout' => $hasLogoutButton,
                'has_myessens' => $hasMyEssens,
                'has_user_data' => $hasUserData,
                'has_auth_error' => $hasAuthError,
                'body_length' => strlen($body),
            ]);
            
            $successIndicators = [
                $isRedirect && !$redirectsToLogin, // Редирект, но не на страницу логина
                ($statusCode === 200 && $hasUserData && ($hasLogoutButton || $hasMyEssens)), // Есть данные пользователя и признаки авторизации
                ($statusCode === 200 && $hasLogoutButton && $hasMyEssens), // Есть и кнопка выхода, и ссылка на личный кабинет
                ($statusCode === 200 && $hasUserData && !$hasAuthError), // Есть данные пользователя и нет ошибок авторизации
            ];

            if (in_array(true, $successIndicators, true)) {
                $this->isAuthenticated = true;
                Log::info("Successfully authenticated to essensworld.ru", [
                    'status' => $statusCode,
                ]);
                return true;
            }

            Log::warning("Authentication failed", [
                'status' => $statusCode,
                'body_preview' => substr($body, 0, 500),
                'has_login_form' => $hasLoginForm,
                'has_error' => $hasError,
                'location' => $location,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Exception during authentication", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Получить HTML страницы
     */
    public function fetchPage(string $url): ?string
    {
        // Убеждаемся, что авторизованы перед запросом
        if (!$this->isAuthenticated) {
            Log::info("Not authenticated, attempting authentication before fetch", ['url' => $url]);
            if (!$this->authenticate()) {
                Log::error("Authentication failed before fetch", ['url' => $url]);
                return null;
            }
        }

        try {
            try {
                $response = $this->getClient()->get($url);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                Log::error("RequestException while fetching page", [
                    'url' => $url,
                    'message' => $e->getMessage(),
                    'has_response' => $e->hasResponse(),
                ]);
                
                // Если есть ответ, попробуем его использовать
                if ($e->hasResponse()) {
                    try {
                        $response = \Illuminate\Support\Facades\Http::response($e->getResponse());
                    } catch (\Exception $ex) {
                        Log::error("Failed to convert exception response", [
                            'url' => $url,
                            'message' => $ex->getMessage(),
                        ]);
                        return null;
                    }
                } else {
                    return null;
                }
            } catch (\Exception $e) {
                Log::error("Exception while fetching page", [
                    'url' => $url,
                    'message' => $e->getMessage(),
                    'class' => get_class($e),
                ]);
                return null;
            }

            if ($response->successful()) {
                $body = $response->body();
                if (empty($body)) {
                    Log::warning("Empty response body", ['url' => $url, 'status' => $response->status()]);
                    return null;
                }
                return $body;
            }

            // Если получили редирект на страницу логина, пробуем авторизоваться снова
            $body = $response->body();
            if ($response->status() === 302 || stripos($body, 'login.php') !== false || stripos($body, 'Вход') !== false) {
                Log::warning("Redirected to login, re-authenticating", [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                $this->isAuthenticated = false;
                if ($this->authenticate()) {
                    // Повторяем запрос после авторизации
                    $response = $this->getClient()->get($url);
                    if ($response->successful()) {
                        $body = $response->body();
                        if (empty($body)) {
                            Log::warning("Empty response body after re-auth", ['url' => $url]);
                            return null;
                        }
                        return $body;
                    }
                } else {
                    Log::error("Re-authentication failed", ['url' => $url]);
                    return null;
                }
            }

            Log::error("Failed to fetch page: {$url}", [
                'status' => $response->status(),
                'body_preview' => substr($body, 0, 500),
                'headers' => $response->headers(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Exception while fetching page: {$url}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Парсинг HTML через DOMDocument
     */
    protected function parseHtml(string $html): ?DOMXPath
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();

        return new DOMXPath($dom);
    }

    /**
     * Получить категории с главной страницы
     */
    public function getCategories(): array
    {
        // Убеждаемся, что авторизованы
        if (!$this->isAuthenticated) {
            if (!$this->authenticate()) {
                Log::error("Failed to authenticate before fetching categories");
                return [];
            }
        }

        // Получаем страницу eshop, где находятся категории
        $eshopUrl = $this->baseUrl . '/eshop/';
        $html = $this->fetchPage($eshopUrl);
        if (!$html) {
            Log::warning("Failed to fetch eshop page for categories", ['url' => $eshopUrl]);
            // Пробуем главную страницу как fallback
            $html = $this->fetchPage($this->baseUrl);
            if (!$html) {
                Log::warning("Failed to fetch main page for categories");
                return [];
            }
        }

        $xpath = $this->parseHtml($html);
        if (!$xpath) {
            Log::warning("Failed to parse HTML for categories");
            return [];
        }

        $categories = [];
        
        // Ищем категории в элементе с id="accordian" (основной источник категорий)
        $accordianNode = $xpath->query("//*[@id='accordian'] | //*[@id='accordion']")->item(0);
        
        if ($accordianNode) {
            Log::info("Found accordian element, extracting categories");
            
            // Ищем все ссылки внутри accordian
            $categoryLinks = $xpath->query(".//a[@href]", $accordianNode);
            
            foreach ($categoryLinks as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);
                
                // Пропускаем пустые или слишком короткие названия
                if (!$name || strlen($name) < 2) {
                    continue;
                }
                
                // Пропускаем служебные ссылки
                if (stripos($href, 'login') !== false || 
                    stripos($href, 'register') !== false ||
                    stripos($href, 'cart') !== false ||
                    stripos($href, 'javascript:') !== false ||
                    stripos($href, '#') !== false) {
                    continue;
                }
                
                // Формируем полный URL
                if (str_starts_with($href, 'http')) {
                    $fullUrl = $href;
                } else {
                    $fullUrl = $this->baseUrl . '/' . ltrim($href, '/');
                }
                
                // Пропускаем товары (паттерн -d123456/)
                if (preg_match('/-d\d+\/$/', $fullUrl)) {
                    continue;
                }
                
                // Проверяем, что это ссылка на essensworld.ru
                if (stripos($fullUrl, 'essensworld.ru') !== false) {
                    $normalizedUrl = rtrim($fullUrl, '/');
                    $categories[] = [
                        'name' => $name,
                        'url' => $normalizedUrl,
                        'slug' => $this->extractSlugFromUrl($normalizedUrl),
                    ];
                }
            }
            
            Log::info("Found categories in accordian", [
                'count' => count($categories),
            ]);
        }
        
        // Если не нашли в accordian, используем альтернативные методы
        if (count($categories) === 0) {
            Log::info("No categories found in accordian, trying alternative methods");
            
            // Различные варианты селекторов для поиска категорий на essensworld.ru
            $selectors = [
                // Навигационное меню
                "//nav[contains(@class, 'navbar')]//a[contains(@href, '/catalog') or contains(@href, '/category')]",
                "//ul[contains(@class, 'navbar-nav')]//a[contains(@href, '/catalog')]",
                "//div[contains(@class, 'navbar')]//a[contains(@href, '/catalog')]",
                // Меню категорий
                "//div[contains(@class, 'menu')]//a[contains(@href, '/catalog')]",
                "//ul[contains(@class, 'menu')]//a[contains(@href, '/catalog')]",
                // Offcanvas меню
                "//div[contains(@class, 'offcanvas')]//a[contains(@href, '/catalog')]",
                "//div[contains(@class, 'offcanvas-body')]//a[contains(@href, '/catalog')]",
                // Общие селекторы
                "//a[contains(@href, '/catalog/') and not(contains(@href, '/product'))]",
                "//a[contains(@href, '/category/')]",
                // Ссылки в контенте
                "//div[contains(@class, 'content')]//a[contains(@href, '/catalog')]",
            ];
        
        foreach ($selectors as $selector) {
            $categoryNodes = $xpath->query($selector);
            
            if ($categoryNodes->length > 0) {
                foreach ($categoryNodes as $node) {
                    $href = $node->getAttribute('href');
                    $name = trim($node->textContent);
                    
                    // Пропускаем пустые или слишком короткие названия
                    if (!$name || strlen($name) < 2) {
                        continue;
                    }
                    
                    // Пропускаем ссылки на товары
                    if (preg_match('/\/product\/|\/gely-|\/item-|\/nabor-/', $href)) {
                        continue;
                    }
                    
                    // Пропускаем служебные ссылки
                    if (stripos($href, 'login') !== false || 
                        stripos($href, 'register') !== false ||
                        stripos($href, 'cart') !== false) {
                        continue;
                    }
                    
                    // Формируем полный URL
                    if (str_starts_with($href, 'http')) {
                        $fullUrl = $href;
                    } else {
                        $fullUrl = $this->baseUrl . '/' . ltrim($href, '/');
                    }
                    
                    // Проверяем, что это действительно категория (содержит /catalog/ или /category/)
                    if (stripos($fullUrl, '/catalog/') !== false || stripos($fullUrl, '/category/') !== false) {
                        $categories[] = [
                            'name' => $name,
                            'url' => $fullUrl,
                            'slug' => $this->extractSlugFromUrl($fullUrl),
                        ];
                    }
                }
                
                if (count($categories) > 0) {
                    Log::info("Found categories using selector", [
                        'selector' => $selector,
                        'count' => count($categories),
                    ]);
                    break; // Если нашли категории, прекращаем поиск
                }
            }
        }

            // Если не нашли через селекторы, попробуем найти все ссылки с /catalog/ или /category/
            if (count($categories) === 0) {
            Log::info("Trying alternative method to find categories");
            
            // Ищем все ссылки, которые могут быть категориями
            $allLinks = $xpath->query("//a[@href]");
            foreach ($allLinks as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);
                
                // Пропускаем пустые или слишком короткие названия
                if (!$name || strlen($name) < 2) {
                    continue;
                }
                
                // Ищем ссылки на категории
                $isCategoryLink = (
                    stripos($href, '/catalog/') !== false || 
                    stripos($href, '/category/') !== false ||
                    (stripos($href, 'essensworld.ru') !== false && 
                     stripos($href, '/product/') === false &&
                     stripos($href, '/gely-') === false &&
                     stripos($href, '/item-') === false &&
                     stripos($href, '/nabor-') === false &&
                     stripos($href, 'login') === false &&
                     stripos($href, 'register') === false &&
                     stripos($href, 'cart') === false &&
                     preg_match('/\/[a-z-]+\/$/', $href)) // Паттерн типа /category-name/
                );
                
                if ($isCategoryLink) {
                    $fullUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl . '/' . ltrim($href, '/');
                    
                    // Проверяем, что это не товар (товары обычно имеют паттерн -d123456/)
                    if (!preg_match('/-d\d+\/$/', $fullUrl)) {
                        $categories[] = [
                            'name' => $name,
                            'url' => $fullUrl,
                            'slug' => $this->extractSlugFromUrl($fullUrl),
                        ];
                    }
                }
            }
            
            Log::info("Alternative method found categories", [
                'count' => count($categories),
            ]);
            }
            
            // Если все еще не нашли, попробуем найти через структуру меню
            if (count($categories) === 0) {
            Log::info("Trying menu structure method");
            
            // Ищем в структуре меню (ul/li/a)
            $menuLinks = $xpath->query("//ul//li//a[@href] | //nav//ul//li//a[@href] | //div[contains(@class, 'menu')]//a[@href]");
            foreach ($menuLinks as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);
                
                if ($name && strlen($name) > 2) {
                    // Пропускаем служебные ссылки
                    if (stripos($href, 'login') !== false || 
                        stripos($href, 'register') !== false ||
                        stripos($href, 'cart') !== false ||
                        stripos($href, 'javascript:') !== false) {
                        continue;
                    }
                    
                    $fullUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl . '/' . ltrim($href, '/');
                    
                    // Если это ссылка на essensworld.ru и не товар, добавляем
                    if (stripos($fullUrl, 'essensworld.ru') !== false && 
                        !preg_match('/-d\d+\/$/', $fullUrl) &&
                        stripos($fullUrl, '/product/') === false) {
                        $categories[] = [
                            'name' => $name,
                            'url' => $fullUrl,
                            'slug' => $this->extractSlugFromUrl($fullUrl),
                        ];
                    }
                }
            }
            
            Log::info("Menu structure method found categories", [
                'count' => count($categories),
            ]);
            }
        } // Закрываем блок if (count($categories) === 0) для альтернативных методов

        // Удаляем дубликаты по URL
        $uniqueCategories = [];
        $seenUrls = [];
        foreach ($categories as $category) {
            $normalizedUrl = rtrim($category['url'], '/');
            if (!in_array($normalizedUrl, $seenUrls)) {
                $uniqueCategories[] = $category;
                $seenUrls[] = $normalizedUrl;
            }
        }

        // Если все еще не нашли, попробуем найти любые ссылки на essensworld.ru
        if (count($uniqueCategories) === 0) {
            Log::info("Trying to find any links to essensworld.ru");
            
            $allLinks = $xpath->query("//a[@href]");
            $foundUrls = [];
            
            foreach ($allLinks as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);
                
                if (!$name || strlen($name) < 2) {
                    continue;
                }
                
                // Нормализуем URL
                if (str_starts_with($href, 'http')) {
                    $fullUrl = $href;
                } else {
                    $fullUrl = $this->baseUrl . '/' . ltrim($href, '/');
                }
                
                // Проверяем, что это ссылка на essensworld.ru
                if (stripos($fullUrl, 'essensworld.ru') === false) {
                    continue;
                }
                
                // Пропускаем служебные страницы
                if (stripos($fullUrl, 'login') !== false || 
                    stripos($fullUrl, 'register') !== false ||
                    stripos($fullUrl, 'cart') !== false ||
                    stripos($fullUrl, 'eshop.php') !== false) {
                    continue;
                }
                
                // Пропускаем товары (паттерн -d123456/)
                if (preg_match('/-d\d+\/$/', $fullUrl)) {
                    continue;
                }
                
                // Если это не главная страница и не товар, возможно это категория
                $path = parse_url($fullUrl, PHP_URL_PATH);
                if ($path && $path !== '/' && $path !== '/index.php' && $path !== '/eshop.php') {
                    $normalizedUrl = rtrim($fullUrl, '/');
                    if (!in_array($normalizedUrl, $foundUrls)) {
                        $foundUrls[] = $normalizedUrl;
                        $uniqueCategories[] = [
                            'name' => $name,
                            'url' => $normalizedUrl,
                            'slug' => $this->extractSlugFromUrl($normalizedUrl),
                        ];
                    }
                }
            }
            
            Log::info("Found links method result", [
                'total_links_checked' => $allLinks->length,
                'categories_found' => count($uniqueCategories),
            ]);
        }

        Log::info("Categories found", [
            'total' => count($uniqueCategories),
            'categories' => array_map(function($cat) {
                return ['name' => $cat['name'], 'url' => $cat['url']];
            }, array_slice($uniqueCategories, 0, 10)), // Логируем первые 10 для примера
        ]);

        return $uniqueCategories;
    }

    /**
     * Получить товары из категории
     */
    public function getProductsFromCategory(string $categoryUrl, int $page = 1): array
    {
        $url = $categoryUrl . ($page > 1 ? "?page={$page}" : '');
        $html = $this->fetchPage($url);
        
        if (!$html) {
            return [];
        }

        $xpath = $this->parseHtml($html);
        if (!$xpath) {
            return [];
        }

        $products = [];
        
        // Попробуем найти карточки товаров
        // Селекторы нужно адаптировать под реальную структуру
        $productNodes = $xpath->query("//div[contains(@class, 'product')]//a[contains(@href, '/gely') or contains(@href, '/product')]");
        
        foreach ($productNodes as $node) {
            $href = $node->getAttribute('href');
            if ($href) {
                $fullUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl . $href;
                $products[] = [
                    'url' => $fullUrl,
                    'name' => trim($node->textContent),
                ];
            }
        }

        return $products;
    }

    /**
     * Парсинг товара по прямой ссылке
     */
    public function parseProduct(string $productUrl): ?array
    {
        try {
            $html = $this->fetchPage($productUrl);
            if (!$html) {
                Log::error("Failed to fetch product page", [
                    'url' => $productUrl,
                    'authenticated' => $this->isAuthenticated,
                ]);
                return null;
            }

            $xpath = $this->parseHtml($html);
            if (!$xpath) {
                Log::error("Failed to parse HTML", [
                    'url' => $productUrl,
                    'html_length' => strlen($html),
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error("Exception in parseProduct", [
                'url' => $productUrl,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }

        $product = [
            'url' => $productUrl,
            'name' => null,
            'description' => null,
            'price' => null,
            'old_price' => null,
            'discounted_price' => null,
            'recommended_price' => null,
            'lowest_recent_price' => null,
            'points' => null,
            'cashback' => null,
            'images' => [],
            'sku' => null,
            'in_stock' => true,
            'volume' => null,
            'rating' => null,
            'reviews_count' => null,
            'attributes' => [],
        ];

        // Парсинг названия товара (для essensworld.ru)
        $nameSelectors = [
            "//h1[contains(@class, 'product-h')]",
            "//h1[contains(@class, 'product-title') or contains(@class, 'title') or contains(@class, 'product-name')]",
            "//h1",
            "//div[contains(@class, 'product-title')]//h1",
            "//h1[@itemprop='name']",
        ];
        
        foreach ($nameSelectors as $selector) {
            $nameNodes = $xpath->query($selector);
            if ($nameNodes->length > 0) {
                $product['name'] = trim($nameNodes->item(0)->textContent);
                if ($product['name']) break;
            }
        }

        // Парсинг описания (для essensworld.ru - в accordion)
        $descSelectors = [
            "//div[@id='collapse-description']//div[contains(@class, 'accordion-body')]//p",
            "//div[@id='collapse-description']//div[contains(@class, 'accordion-body')]",
            "//button[contains(text(), 'Описание продукта')]/following::div[contains(@class, 'accordion-body')]//p",
            "//div[contains(@class, 'description') or contains(@class, 'product-description')]",
            "//div[@itemprop='description']",
            "//div[contains(@class, 'content')]//p",
        ];
        
        $descriptionParts = [];
        foreach ($descSelectors as $selector) {
            $descNodes = $xpath->query($selector);
            if ($descNodes->length > 0) {
                foreach ($descNodes as $node) {
                    $text = trim($node->textContent);
                    // Пропускаем заголовки и короткие тексты
                    if ($text && strlen($text) > 30 && !in_array($text, $descriptionParts)) {
                        // Убираем лишние пробелы и переносы
                        $text = preg_replace('/\s+/', ' ', $text);
                        $descriptionParts[] = $text;
                    }
                }
                if (count($descriptionParts) > 0) break;
            }
        }
        
        if (count($descriptionParts) > 0) {
            $product['description'] = implode("\n\n", array_filter($descriptionParts));
        }

        // Парсинг цен (для essensworld.ru)
        // Ищем блок price-wrap
        $priceWrap = $xpath->query("//div[contains(@class, 'price-wrap')]")->item(0);
        
        if ($priceWrap) {
            // Ищем цену со скидкой (discounted-price) - это новая цена
            $discountedPriceNode = $xpath->query(".//div[contains(@class, 'discounted-price')]", $priceWrap)->item(0);
            if ($discountedPriceNode) {
                $priceText = trim($discountedPriceNode->textContent);
                $price = $this->extractPrice($priceText);
                if ($price && $price > 0) {
                    $product['discounted_price'] = $price;
                    $product['price'] = $price; // Основная цена = цена со скидкой
                }
            }
            
            // Старая цена (цена до скидки) - в div с классами price и discount
            $oldPriceNode = $xpath->query(".//div[contains(@class, 'price') and contains(@class, 'discount')]", $priceWrap)->item(0);
            if ($oldPriceNode) {
                $priceText = trim($oldPriceNode->textContent);
                $price = $this->extractPrice($priceText);
                if ($price && $price > 0) {
                    $product['old_price'] = $price;
                }
            }
            
            // Если не нашли цену со скидкой, ищем обычную цену (без класса discount)
            if (!$product['price']) {
                $regularPriceNode = $xpath->query(".//div[contains(@class, 'price') and not(contains(@class, 'discount'))]", $priceWrap)->item(0);
                if ($regularPriceNode) {
                    $priceText = trim($regularPriceNode->textContent);
                    $price = $this->extractPrice($priceText);
                    if ($price && $price > 0) {
                        $product['price'] = $price;
                    }
                }
            }
        } else {
            // Fallback: ищем цены без price-wrap
            $discountedPriceNode = $xpath->query("//div[contains(@class, 'discounted-price')]")->item(0);
            if ($discountedPriceNode) {
                $priceText = trim($discountedPriceNode->textContent);
                $price = $this->extractPrice($priceText);
                if ($price && $price > 0) {
                    $product['discounted_price'] = $price;
                    $product['price'] = $price;
                }
            }
            
            $oldPriceNode = $xpath->query("//div[contains(@class, 'price') and contains(@class, 'discount')]")->item(0);
            if ($oldPriceNode) {
                $priceText = trim($oldPriceNode->textContent);
                $price = $this->extractPrice($priceText);
                if ($price && $price > 0) {
                    $product['old_price'] = $price;
                }
            }
        }
        
        // Лучшая цена за последние 30 дней
        $lowestPriceNode = $xpath->query("//div[contains(@class, 'lowest-recent-price')]")->item(0);
        if ($lowestPriceNode) {
            // Проверяем, что элемент не скрыт (нет класса d-none)
            $classAttr = $lowestPriceNode->getAttribute('class');
            if ($classAttr && stripos($classAttr, 'd-none') === false) {
                $priceText = trim($lowestPriceNode->textContent);
                // Извлекаем цену из текста типа "Лучшая цена за последние 30 дней: 450,00 руб"
                if (preg_match('/(\d+[\s,.]?\d*)\s*руб/ui', $priceText, $matches)) {
                    $priceText = str_replace([' ', ','], ['', '.'], $matches[1]);
                    $product['lowest_recent_price'] = (float) $priceText;
                }
            }
        }
        
        // Рекомендуемая розничная цена (из блока recommended-price-wrap или details-section-wrap)
        $recommendedPriceWrap = $xpath->query("//div[contains(@class, 'recommended-price-wrap')] | //div[contains(@class, 'details-section-wrap')]//div[contains(@class, 'recommended-price-wrap')]")->item(0);
        if ($recommendedPriceWrap) {
            $recommendedPriceNode = $xpath->query(".//div[contains(@class, 'recommended-price')]", $recommendedPriceWrap)->item(0);
            if ($recommendedPriceNode) {
                $priceText = trim($recommendedPriceNode->textContent);
                $price = $this->extractPrice($priceText);
                if ($price && $price > 0) {
                    $product['recommended_price'] = $price;
                }
            }
        } else {
            // Fallback: ищем без обертки
            $recommendedPriceNode = $xpath->query("//div[contains(@class, 'recommended-price')]")->item(0);
            if ($recommendedPriceNode) {
                $priceText = trim($recommendedPriceNode->textContent);
                $price = $this->extractPrice($priceText);
                if ($price && $price > 0) {
                    $product['recommended_price'] = $price;
                }
            }
        }
        
        // Бонусные баллы
        $pointsNode = $xpath->query("//div[contains(@class, 'points')]")->item(0);
        if ($pointsNode) {
            $pointsText = trim($pointsNode->textContent);
            // Извлекаем число из текста типа "3,50 б"
            if (preg_match('/(\d+[.,]\d+|\d+)\s*б/ui', $pointsText, $matches)) {
                $pointsText = str_replace(',', '.', $matches[1]);
                $product['points'] = (float) $pointsText;
            }
        }
        
        // Кэшбэк
        $cashbackNode = $xpath->query("//div[contains(@class, 'cashback-wrap')]//div[contains(@class, 'cashback')]")->item(0);
        if ($cashbackNode) {
            $cashbackText = trim($cashbackNode->textContent);
            $cashback = $this->extractPrice($cashbackText);
            if ($cashback && $cashback > 0) {
                $product['cashback'] = $cashback;
            }
        }
        
        // Если не нашли через XPath, используем regex как fallback
        if (!$product['price'] && preg_match('/(\d+[\s,.]?\d*)\s*руб/ui', $html, $matches)) {
            $priceText = str_replace([' ', ','], ['', '.'], $matches[1]);
            $product['price'] = (float) $priceText;
        }

        // Парсинг изображений (для essensworld.ru)
        $imageSelectors = [
            "//img[contains(@class, 'detail-photo-img')]",
            "//a[contains(@class, 'detail-photo')]//img",
            "//div[contains(@class, 'swiper-side-photos')]//img",
            "//div[contains(@class, 'photos-col')]//img",
            "//img[contains(@src, 'static.essensworld.com/images/goods')]",
            "//img[contains(@class, 'product-image') or contains(@class, 'product-photo')]",
            "//div[contains(@class, 'product-image')]//img",
            "//img[@itemprop='image']",
            "//img[contains(@src, '/images/goods/')]", // Более общий селектор
        ];
        
        $foundImages = [];
        foreach ($imageSelectors as $selector) {
            $imageNodes = $xpath->query($selector);
            if ($imageNodes->length > 0) {
                Log::info("Found images with selector", [
                    'selector' => $selector,
                    'count' => $imageNodes->length,
                    'url' => $productUrl,
                ]);
                
                foreach ($imageNodes as $img) {
                    $src = $img->getAttribute('src') ?: $img->getAttribute('data-src') ?: $img->getAttribute('data-lazy-src');
                    if ($src) {
                        // Пропускаем логотипы, иконки и маленькие превью
                        if (stripos($src, 'logo') !== false || 
                            stripos($src, 'icon') !== false || 
                            stripos($src, 'thumb') !== false ||
                            stripos($src, '/r/200/') !== false ||
                            stripos($src, 'coupon') !== false) {
                            continue;
                        }
                        
                        // Преобразуем относительные URL в абсолютные
                        if (str_starts_with($src, '//')) {
                            $fullSrc = 'https:' . $src;
                        } elseif (str_starts_with($src, 'http')) {
                            $fullSrc = $src;
                        } elseif (str_starts_with($src, '/static.')) {
                            // Обработка путей вида /static.essensworld.com/...
                            $fullSrc = 'https://www' . $src;
                        } else {
                            $fullSrc = $this->baseUrl . '/' . ltrim($src, '/');
                        }
                        
                        if (!in_array($fullSrc, $product['images']) && !in_array($fullSrc, $foundImages)) {
                            $product['images'][] = $fullSrc;
                            $foundImages[] = $fullSrc;
                            Log::info("Added product image", [
                                'url' => $productUrl,
                                'image_url' => $fullSrc,
                            ]);
                        }
                    }
                }
                if (count($product['images']) > 0) break;
            }
        }
        
        // Также ищем изображения в ссылках галереи
        if (count($product['images']) == 0) {
            $galleryLinks = $xpath->query("//a[contains(@class, 'detail-photo')]/@href");
            Log::info("Searching in gallery links", [
                'count' => $galleryLinks->length,
                'url' => $productUrl,
            ]);
            
            foreach ($galleryLinks as $link) {
                $href = $link->nodeValue;
                if ($href && (stripos($href, 'static.essensworld.com/images/goods') !== false || stripos($href, '/images/goods/') !== false)) {
                    if (str_starts_with($href, '//')) {
                        $fullSrc = 'https:' . $href;
                    } elseif (str_starts_with($href, 'http')) {
                        $fullSrc = $href;
                    } elseif (str_starts_with($href, '/static.')) {
                        $fullSrc = 'https://www' . $href;
                    } else {
                        $fullSrc = $this->baseUrl . '/' . ltrim($href, '/');
                    }
                    if (!in_array($fullSrc, $product['images']) && !in_array($fullSrc, $foundImages)) {
                        $product['images'][] = $fullSrc;
                        $foundImages[] = $fullSrc;
                        Log::info("Added product image from gallery link", [
                            'url' => $productUrl,
                            'image_url' => $fullSrc,
                        ]);
                    }
                }
            }
        }
        
        Log::info("Product images parsing result", [
            'url' => $productUrl,
            'images_count' => count($product['images']),
            'images' => array_slice($product['images'], 0, 3), // Логируем первые 3 для отладки
        ]);

        // Парсинг артикула/SKU (для essensworld.ru)
        $skuSelectors = [
            "//span[contains(@class, 'lhi-goo-code')]",
            "//div[contains(@class, 'goo-code')]//span",
            "//span[contains(text(), 'Артикул') or contains(text(), 'SKU')]/following-sibling::span",
            "//*[contains(@class, 'sku') or contains(@class, 'article') or contains(@class, 'goo-code')]",
        ];
        
        foreach ($skuSelectors as $selector) {
            $skuNodes = $xpath->query($selector);
            if ($skuNodes->length > 0) {
                $product['sku'] = trim($skuNodes->item(0)->textContent);
                if ($product['sku']) break;
            }
        }
        
        // Если артикул не найден, ищем в тексте страницы (например, "col14")
        if (!$product['sku']) {
            // Ищем паттерны типа "col14", "col14n" в тексте
            if (preg_match('/\b([a-z]{2,4}\d{2,6}[a-z]?)\b/i', $html, $matches)) {
                $product['sku'] = trim($matches[1]);
            }
        }

        // Проверка наличия (для essensworld.ru)
        $stockSelectors = [
            "//span[contains(@class, 'green') and contains(text(), 'на складе')]",
            "//div[contains(@class, 'detail-availability')]//span[contains(@class, 'green')]",
            "//*[contains(text(), 'на складе')]",
            "//*[contains(text(), 'В наличии')]",
            "//*[contains(text(), 'Нет в наличии') or contains(text(), 'out of stock')]",
        ];
        
        $product['in_stock'] = true; // По умолчанию
        foreach ($stockSelectors as $selector) {
            $stockNodes = $xpath->query($selector);
            if ($stockNodes->length > 0) {
                foreach ($stockNodes as $node) {
                    $stockText = trim($node->textContent);
                    if (stripos($stockText, 'на складе') !== false || stripos($stockText, 'в наличии') !== false) {
                        $product['in_stock'] = true;
                        break 2;
                    } elseif (stripos($stockText, 'нет в наличии') !== false || stripos($stockText, 'out of stock') !== false) {
                        $product['in_stock'] = false;
                        break 2;
                    }
                }
            }
        }
        
        // Дополнительные данные
        // Объем/размер (для essensworld.ru)
        $volumeSelectors = [
            "//div[contains(@class, 'details-section-h') and contains(text(), 'Объем')]/following-sibling::div",
            "//div[contains(@class, 'details-section-wrap')]//div[contains(@class, 'details-section-h') and contains(text(), 'Объем')]/following-sibling::div[1]",
            "//*[contains(text(), 'Объем')]/following-sibling::*",
        ];
        foreach ($volumeSelectors as $selector) {
            $volumeNodes = $xpath->query($selector);
            if ($volumeNodes->length > 0) {
                $product['volume'] = trim($volumeNodes->item(0)->textContent);
                if ($product['volume']) break;
            }
        }
        
        // Рейтинг (для essensworld.ru)
        $ratingSelectors = [
            "//div[contains(@class, 'stars-wrap')]//strong",
            "//a[contains(@href, 'collapse-reviews')]//strong",
        ];
        foreach ($ratingSelectors as $selector) {
            $ratingNodes = $xpath->query($selector);
            if ($ratingNodes->length > 0) {
                $ratingText = trim($ratingNodes->item(0)->textContent);
                if (preg_match('/(\d+[.,]\d+)/', $ratingText, $matches)) {
                    $product['rating'] = (float) str_replace(',', '.', $matches[1]);
                    break;
                }
            }
        }
        
        // Старая цена (если есть скидка)
        $oldPriceSelectors = [
            "//div[contains(@class, 'price') and contains(@class, 'discount')]",
            "//div[contains(@class, 'price-wrap')]//div[contains(@class, 'price') and contains(@class, 'discount')]",
        ];
        foreach ($oldPriceSelectors as $selector) {
            $oldPriceNodes = $xpath->query($selector);
            if ($oldPriceNodes->length > 0) {
                $oldPriceText = trim($oldPriceNodes->item(0)->textContent);
                $oldPrice = $this->extractPrice($oldPriceText);
                if ($oldPrice && $oldPrice > 0) {
                    $product['old_price'] = $oldPrice;
                    break;
                }
            }
        }
        
        // Рекомендуемая цена
        $recommendedPriceNodes = $xpath->query("//div[contains(@class, 'recommended-price')]");
        if ($recommendedPriceNodes->length > 0) {
            $recPriceText = trim($recommendedPriceNodes->item(0)->textContent);
            $recPrice = $this->extractPrice($recPriceText);
            if ($recPrice && $recPrice > 0) {
                $product['recommended_price'] = $recPrice;
            }
        }

        return $product;
    }

    /**
     * Извлечь цену из текста
     */
    protected function extractPrice(string $text): ?float
    {
        // Удаляем все кроме цифр, точек и запятых
        $cleaned = preg_replace('/[^\d,.]/', '', $text);
        $cleaned = str_replace(',', '.', $cleaned);
        
        return $cleaned ? (float) $cleaned : null;
    }

    /**
     * Извлечь slug из URL
     */
    protected function extractSlugFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $segments = explode('/', trim($path, '/'));
        return end($segments) ?: '';
    }

    /**
     * Проверить доступность сайта
     */
    public function checkAvailability(): array
    {
        try {
            $cookieJar = $this->getCookieJar();
            
            $response = Http::timeout(10)
                ->withOptions([
                    'cookies' => $cookieJar,
                    'verify' => false, // Отключаем проверку SSL
                    'allow_redirects' => [
                        'max' => 10,
                        'strict' => false,
                        'referer' => true,
                        'protocols' => ['http', 'https'],
                    ],
                    'http_errors' => false,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($this->baseUrl);

            return [
                'available' => $response->successful(),
                'status_code' => $response->status(),
                'response_time' => $response->transferStats?->getTransferTime() ?? null,
            ];
        } catch (\Exception $e) {
            Log::error("Error checking availability", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'available' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Проверить статус авторизации
     */
    public function checkAuthentication(): array
    {
        try {
            $authenticated = $this->authenticate();
            return [
                'authenticated' => $authenticated,
                'message' => $authenticated ? 'Авторизация успешна' : 'Ошибка авторизации',
            ];
        } catch (\Exception $e) {
            return [
                'authenticated' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Получить категории с eshop.php
     */
    public function getEshopCategories(): array
    {
        // Убеждаемся, что авторизованы
        if (!$this->isAuthenticated) {
            if (!$this->authenticate()) {
                Log::error("Failed to authenticate before fetching eshop categories");
                return [];
            }
        }

        // Пробуем несколько вариантов URL
        $urlsToTry = [
            $this->baseUrl . '/eshop/',
            $this->baseUrl . '/eshop.php',
            $this->baseUrl . '/eshop.php?cat_id=0',
            $this->baseUrl, // Главная страница
        ];

        $html = null;
        $usedUrl = null;
        
        foreach ($urlsToTry as $url) {
            $html = $this->fetchPage($url);
            if ($html) {
                $usedUrl = $url;
                Log::info("Successfully fetched page for eshop categories", ['url' => $url, 'html_length' => strlen($html)]);
                break;
            }
        }

        if (!$html) {
            Log::warning("Failed to fetch any page for eshop categories", ['tried_urls' => $urlsToTry]);
            return [];
        }

        $xpath = $this->parseHtml($html);
        if (!$xpath) {
            Log::warning("Failed to parse HTML for eshop categories");
            return [];
        }

        $categories = [];

        // Сначала ищем в accordian элементе (как в getCategories)
        $accordianNode = $xpath->query("//*[@id='accordian'] | //*[@id='accordion']")->item(0);
        
        if ($accordianNode) {
            Log::info("Found accordian element, extracting eshop categories with subcategories");
            
            // Находим контейнер panel panel-default (все категории находятся в одной панели)
            $panelContainer = $xpath->query(".//div[contains(@class, 'panel') and contains(@class, 'panel-default')]", $accordianNode)->item(0);
            
            if (!$panelContainer) {
                // Если не нашли panel-default, используем сам accordian
                $panelContainer = $accordianNode;
            }
            
            // Находим все panel-heading (категории) внутри контейнера
            $panelHeadings = $xpath->query(".//div[contains(@class, 'panel-heading') and contains(@class, 'new-menu-heading')]", $panelContainer);
            
            $foundCatIds = [];
            $categories = [];
            
            foreach ($panelHeadings as $index => $panelHeading) {
                // Ищем ссылку категории в panel-title
                $categoryLink = $xpath->query(".//span[contains(@class, 'panel-title')]//a[@href] | .//a[@href and contains(@class, 'new-menu-collapse')]", $panelHeading)->item(0);
                
                if (!$categoryLink) {
                    continue;
                }
                
                $mainHref = $categoryLink->getAttribute('href');
                $mainName = trim($categoryLink->textContent);
                
                // Очищаем название от HTML тегов (например, NEW флаги)
                $mainName = preg_replace('/<[^>]+>.*?<\/[^>]+>/', '', $mainName);
                $mainName = trim($mainName);
                
                if (!$mainName || strlen($mainName) < 2) {
                    continue;
                }
                
                // Пропускаем служебные ссылки
                if (stripos($mainHref, 'login') !== false || 
                    stripos($mainHref, 'register') !== false ||
                    stripos($mainHref, 'cart') !== false ||
                    stripos($mainHref, 'javascript:') !== false ||
                    $mainHref === '#' || 
                    (str_starts_with($mainHref, '#') && $mainHref !== '#eshop_products')) {
                    continue;
                }
                
                // Формируем полный URL
                if (str_starts_with($mainHref, 'http')) {
                    $mainFullUrl = $mainHref;
                } else {
                    $mainFullUrl = $this->baseUrl . '/' . ltrim($mainHref, '/');
                }
                
                // Пропускаем товары
                if (preg_match('/-d\d+\//', $mainFullUrl)) {
                    continue;
                }
                
                // Извлекаем cat_id основной категории
                $mainCatId = null;
                if (preg_match('/-c(\d+)(?:\/|#|$)/', $mainFullUrl, $matches)) {
                    $mainCatId = (int)$matches[1];
                } elseif (preg_match('/cat_id[=:](\d+)/', $mainHref, $matches)) {
                    $mainCatId = (int)$matches[1];
                }
                
                if (!$mainCatId || $mainCatId <= 0) {
                    continue;
                }
                
                // Ищем подкатегории в следующем panel-collapse после этого panel-heading
                $subcategories = [];
                
                // Ищем data-target в panel-heading для определения ID panel-collapse
                $dataTarget = $xpath->query(".//a[@data-toggle='collapse']/@data-target", $panelHeading)->item(0);
                $collapseClass = null;
                if ($dataTarget) {
                    $targetValue = $dataTarget->nodeValue;
                    // Извлекаем класс из data-target (например, ".s19" -> "s19")
                    if (preg_match('/\.(s\d+)/', $targetValue, $matches)) {
                        $collapseClass = $matches[1];
                    }
                }
                
                // Ищем panel-collapse следующий за этим panel-heading
                $panelCollapse = null;
                if ($collapseClass) {
                    // Ищем по классу
                    $panelCollapse = $xpath->query(".//div[contains(@class, 'panel-collapse') and contains(@class, '{$collapseClass}')]", $panelContainer)->item(0);
                } else {
                    // Ищем следующий элемент после panel-heading
                    $nextSibling = $panelHeading->nextSibling;
                    while ($nextSibling) {
                        if ($nextSibling->nodeType === XML_ELEMENT_NODE && 
                            $nextSibling->getAttribute('class') && 
                            strpos($nextSibling->getAttribute('class'), 'panel-collapse') !== false) {
                            $panelCollapse = $nextSibling;
                            break;
                        }
                        $nextSibling = $nextSibling->nextSibling;
                    }
                }
                
                // Если нашли panel-collapse, извлекаем подкатегории
                if ($panelCollapse) {
                    $subcategoryLinks = $xpath->query(".//div[contains(@class, 'panel-body')]//ul//li//a[@href]", $panelCollapse);
                    
                    foreach ($subcategoryLinks as $subNode) {
                        $subHref = $subNode->getAttribute('href');
                        $subName = trim($subNode->textContent);
                        
                        if (!$subName || strlen($subName) < 2) {
                            continue;
                        }
                        
                        // Пропускаем служебные ссылки
                        if (stripos($subHref, 'login') !== false || 
                            stripos($subHref, 'register') !== false ||
                            stripos($subHref, 'cart') !== false ||
                            stripos($subHref, 'javascript:') !== false ||
                            $subHref === '#' || 
                            (str_starts_with($subHref, '#') && $subHref !== '#eshop_products')) {
                            continue;
                        }
                        
                        // Формируем полный URL
                        if (str_starts_with($subHref, 'http')) {
                            $subFullUrl = $subHref;
                        } else {
                            $subFullUrl = $this->baseUrl . '/' . ltrim($subHref, '/');
                        }
                        
                        // Пропускаем товары
                        if (preg_match('/-d\d+\//', $subFullUrl)) {
                            continue;
                        }
                        
                        // Извлекаем cat_id подкатегории
                        $subCatId = null;
                        if (preg_match('/-c(\d+)(?:\/|#|$)/', $subFullUrl, $matches)) {
                            $subCatId = (int)$matches[1];
                        } elseif (preg_match('/cat_id[=:](\d+)/', $subHref, $matches)) {
                            $subCatId = (int)$matches[1];
                        }
                        
                        if ($subCatId && $subCatId > 0) {
                            $subcategories[] = [
                                'id' => $subCatId,
                                'name' => $subName,
                                'url' => $this->baseUrl . '/eshop.php?cat_id=' . $subCatId,
                            ];
                        }
                    }
                }
                
                // Добавляем категорию с подкатегориями
                if (!in_array($mainCatId, $foundCatIds)) {
                    $foundCatIds[] = $mainCatId;
                    $categories[] = [
                        'id' => $mainCatId,
                        'name' => $mainName,
                        'url' => $this->baseUrl . '/eshop.php?cat_id=' . $mainCatId,
                        'subcategories' => $subcategories,
                    ];
                }
            }
            
            Log::info("Found categories in accordian with subcategories", [
                'total_categories' => count($categories),
                'categories_with_subcategories' => count(array_filter($categories, function($cat) {
                    return !empty($cat['subcategories']);
                })),
                'cat_ids' => array_slice($foundCatIds, 0, 20),
            ]);
        }

        // Расширенный набор селекторов для поиска категорий
        $categorySelectors = [
            // Прямые ссылки на eshop.php с cat_id
            "//a[contains(@href, 'eshop.php') and contains(@href, 'cat_id=')]",
            "//a[contains(@href, 'eshop.php?cat_id=')]",
            "//a[starts-with(@href, 'eshop.php?cat_id=')]",
            
            // Data атрибуты
            "//a[@data-cat-id]",
            "//div[@data-cat-id]//a",
            "//li[@data-cat-id]//a",
            "//span[@data-cat-id]//a",
            "//*[@data-category-id]//a",
            
            // Классы
            "//*[@class='category']//a[contains(@href, 'cat_id')]",
            "//*[contains(@class, 'cat-item')]//a",
            "//*[contains(@class, 'category-item')]//a",
            "//*[contains(@class, 'category-link')]",
            "//*[contains(@class, 'cat-link')]",
            
            // Навигация и меню
            "//nav//a[contains(@href, 'eshop.php')]",
            "//ul[contains(@class, 'menu')]//a[contains(@href, 'cat_id')]",
            "//ul[contains(@class, 'nav')]//a[contains(@href, 'cat_id')]",
            "//div[contains(@class, 'menu')]//a[contains(@href, 'cat_id')]",
            
            // Accordion и выпадающие меню
            "//*[@id='accordian']//a[contains(@href, 'cat_id')]",
            "//*[@id='accordion']//a[contains(@href, 'cat_id')]",
            "//*[contains(@class, 'accordion')]//a[contains(@href, 'cat_id')]",
            "//*[contains(@class, 'collapse')]//a[contains(@href, 'cat_id')]",
        ];

        Log::info("Starting category search with selectors", [
            'selectors_count' => count($categorySelectors),
            'source_url' => $usedUrl,
        ]);

        foreach ($categorySelectors as $index => $selector) {
            $nodes = $xpath->query($selector);
            $foundCount = 0;
            
            Log::debug("Trying selector", [
                'index' => $index,
                'selector' => $selector,
                'nodes_found' => $nodes->length,
            ]);

            foreach ($nodes as $node) {
                $href = $node->getAttribute('href');
                $dataCatId = $node->getAttribute('data-cat-id') ?: $node->getAttribute('data-category-id');
                $name = trim($node->textContent);

                // Пробуем получить название из разных мест
                if (!$name || strlen($name) < 2) {
                    // Пробуем получить из title или alt
                    $name = $node->getAttribute('title') ?: $node->getAttribute('alt') ?: '';
                    $name = trim($name);
                    
                    // Пробуем получить из родительского элемента
                    if (!$name || strlen($name) < 2) {
                        $parent = $node->parentNode;
                        if ($parent) {
                            $name = trim($parent->textContent);
                        }
                    }
                }

                if (!$name || strlen($name) < 2) {
                    continue;
                }

                // Извлекаем cat_id из href или data-cat-id
                $catId = null;
                if ($dataCatId) {
                    $catId = (int)$dataCatId;
                } elseif ($href) {
                    // Формируем полный URL для проверки
                    $fullUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl . '/' . ltrim($href, '/');
                    
                    // Пробуем новый формат: -c{id}/ или -c{id}#
                    if (preg_match('/-c(\d+)(?:\/|#|$)/', $fullUrl, $matches)) {
                        $catId = (int)$matches[1];
                    } elseif (preg_match('/-c(\d+)(?:\/|#|$)/', $href, $matches)) {
                        $catId = (int)$matches[1];
                    }
                    
                    // Пробуем старый формат с cat_id=
                    if (!$catId) {
                        if (preg_match('/cat_id=(\d+)/', $href, $matches)) {
                            $catId = (int)$matches[1];
                        } elseif (preg_match('/cat_id[=:](\d+)/', $href, $matches)) {
                            $catId = (int)$matches[1];
                        } elseif (preg_match('/category[=:](\d+)/', $href, $matches)) {
                            $catId = (int)$matches[1];
                        }
                    }
                }

                if ($catId && $catId > 0) {
                    $categories[] = [
                        'id' => $catId,
                        'name' => $name,
                        'url' => $this->baseUrl . '/eshop.php?cat_id=' . $catId,
                    ];
                    $foundCount++;
                }
            }

            if ($foundCount > 0) {
                Log::info("Found categories with selector", [
                    'selector' => $selector,
                    'count' => $foundCount,
                ]);
            }
        }

        // Если не нашли через селекторы, пробуем найти все ссылки с cat_id
        if (count($categories) === 0) {
            Log::info("No categories found with selectors, trying all links");
            $allLinks = $xpath->query("//a[@href]");
            Log::info("Total links found", ['count' => $allLinks->length]);
            
            $foundCatIds = [];
            foreach ($allLinks as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);

                if (!$name || strlen($name) < 2) {
                    continue;
                }

                // Формируем полный URL
                $fullUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl . '/' . ltrim($href, '/');
                
                // Ищем cat_id в новом формате: -c{id}/ или -c{id}#
                $catId = null;
                if (preg_match('/-c(\d+)(?:\/|#|$)/', $fullUrl, $matches)) {
                    $catId = (int)$matches[1];
                } elseif (preg_match('/-c(\d+)(?:\/|#|$)/', $href, $matches)) {
                    $catId = (int)$matches[1];
                }
                
                // Пробуем старый формат с cat_id=
                if (!$catId) {
                    if (preg_match('/cat_id[=:](\d+)/', $href, $matches)) {
                        $catId = (int)$matches[1];
                    } elseif (preg_match('/eshop\.php.*cat_id[=:](\d+)/', $href, $matches)) {
                        $catId = (int)$matches[1];
                    }
                }

                if ($catId && $catId > 0 && !in_array($catId, $foundCatIds)) {
                    $foundCatIds[] = $catId;
                    $categories[] = [
                        'id' => $catId,
                        'name' => $name,
                        'url' => $this->baseUrl . '/eshop.php?cat_id=' . $catId,
                    ];
                }
            }
            
            Log::info("Found categories from all links", [
                'count' => count($categories),
                'cat_ids' => $foundCatIds,
            ]);
        }

        // Также пробуем найти категории через JavaScript или скрытые элементы
        if (count($categories) === 0) {
            Log::info("Trying to find categories in script tags or hidden elements");
            
            // Ищем в script тегах
            $scriptNodes = $xpath->query("//script");
            foreach ($scriptNodes as $scriptNode) {
                $scriptContent = $scriptNode->textContent;
                // Ищем паттерны типа cat_id: 19 или "cat_id": 19
                if (preg_match_all('/cat_id["\']?\s*[:=]\s*(\d+)/i', $scriptContent, $matches)) {
                    foreach ($matches[1] as $catId) {
                        $catId = (int)$catId;
                        if ($catId > 0) {
                            $categories[] = [
                                'id' => $catId,
                                'name' => 'Категория ' . $catId,
                                'url' => $this->baseUrl . '/eshop.php?cat_id=' . $catId,
                            ];
                        }
                    }
                }
                
                // Ищем массивы категорий в JavaScript
                // Паттерн: [{id: 19, name: "..."}, ...] или {"19": "..."}
                if (preg_match_all('/["\']?id["\']?\s*[:=]\s*(\d+).*?["\']?name["\']?\s*[:=]\s*["\']([^"\']+)["\']/', $scriptContent, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $catId = (int)$match[1];
                        $catName = $match[2] ?? 'Категория ' . $catId;
                        if ($catId > 0) {
                            $categories[] = [
                                'id' => $catId,
                                'name' => $catName,
                                'url' => $this->baseUrl . '/eshop.php?cat_id=' . $catId,
                            ];
                        }
                    }
                }
            }
            
            // Пробуем найти в скрытых input полях или data атрибутах
            $hiddenInputs = $xpath->query("//input[@type='hidden' and contains(@name, 'cat')] | //input[@type='hidden' and contains(@id, 'cat')]");
            foreach ($hiddenInputs as $input) {
                $value = $input->getAttribute('value');
                $name = $input->getAttribute('name') ?: $input->getAttribute('id');
                if ($value && is_numeric($value) && (int)$value > 0) {
                    $categories[] = [
                        'id' => (int)$value,
                        'name' => 'Категория ' . $value . ' (' . $name . ')',
                        'url' => $this->baseUrl . '/eshop.php?cat_id=' . $value,
                    ];
                }
            }
        }

        // Удаляем дубликаты по ID
        $uniqueCategories = [];
        $seenIds = [];
        foreach ($categories as $category) {
            if (!in_array($category['id'], $seenIds)) {
                $uniqueCategories[] = $category;
                $seenIds[] = $category['id'];
            }
        }

        // Сортируем по ID
        usort($uniqueCategories, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });

        // Если все еще не нашли, пробуем парсить eshop.php напрямую с известными cat_id
        // для извлечения названий категорий
        if (count($uniqueCategories) === 0) {
            Log::info("No categories found with eshop-specific methods, trying to parse eshop.php directly");
            
            // Пробуем известные cat_id (обычно начинаются с небольших чисел)
            $knownCatIds = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20];
            
            foreach ($knownCatIds as $testCatId) {
                try {
                    $testUrl = $this->baseUrl . '/eshop.php?cat_id=' . $testCatId . '&load-more=1';
                    $testHtml = $this->fetchPage($testUrl);
                    
                    if ($testHtml) {
                        $testXpath = $this->parseHtml($testHtml);
                        if ($testXpath) {
                            // Ищем заголовок категории на странице
                            $titleSelectors = [
                                "//h1",
                                "//h2",
                                "//title",
                                "//*[@class='category-title']",
                                "//*[@class='page-title']",
                                "//*[contains(@class, 'title')]",
                            ];
                            
                            $categoryName = null;
                            foreach ($titleSelectors as $selector) {
                                $titleNodes = $testXpath->query($selector);
                                if ($titleNodes->length > 0) {
                                    $categoryName = trim($titleNodes->item(0)->textContent);
                                    if ($categoryName && strlen($categoryName) > 2) {
                                        break;
                                    }
                                }
                            }
                            
                            // Если нашли название или страница содержит товары, считаем категорию валидной
                            if ($categoryName || stripos($testHtml, 'product') !== false || stripos($testHtml, 'товар') !== false) {
                                $uniqueCategories[] = [
                                    'id' => $testCatId,
                                    'name' => $categoryName ?: 'Категория ' . $testCatId,
                                    'url' => $this->baseUrl . '/eshop.php?cat_id=' . $testCatId,
                                ];
                                Log::info("Found valid category by testing cat_id", [
                                    'cat_id' => $testCatId,
                                    'name' => $categoryName,
                                ]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // Пропускаем ошибки при тестировании отдельных cat_id
                    continue;
                }
                
                // Ограничиваем количество проверок для производительности
                if (count($uniqueCategories) >= 10) {
                    break;
                }
            }
        }

        // Если все еще не нашли категории, но нашли accordian, 
        // значит проблема в извлечении cat_id - логируем для отладки
        if (count($uniqueCategories) === 0 && $accordianNode) {
            Log::warning("Accordian found but no categories extracted", [
                'source_url' => $usedUrl,
                'accordian_exists' => true,
            ]);
            
            // Пробуем извлечь все ссылки из accordian для отладки
            $debugLinks = $xpath->query(".//a[@href]", $accordianNode);
            $debugUrls = [];
            foreach ($debugLinks as $link) {
                $debugHref = $link->getAttribute('href');
                $debugName = trim($link->textContent);
                if ($debugName && strlen($debugName) > 2) {
                    $debugUrls[] = [
                        'href' => $debugHref,
                        'name' => substr($debugName, 0, 50), // Первые 50 символов
                    ];
                }
            }
            Log::info("Debug: Links found in accordian", [
                'total_links' => $debugLinks->length,
                'sample_links' => array_slice($debugUrls, 0, 10),
            ]);
        }

        Log::info("Eshop categories search completed", [
            'total_found' => count($uniqueCategories),
            'source_url' => $usedUrl,
            'categories' => array_slice($uniqueCategories, 0, 10), // Логируем первые 10
        ]);

        return $uniqueCategories;
    }

    /**
     * Получить товары из категории через eshop.php
     */
    public function getProductsFromEshopCategory(int $catId, int $page = 1): array
    {
        // Убеждаемся, что авторизованы
        if (!$this->isAuthenticated) {
            if (!$this->authenticate()) {
                Log::error("Failed to authenticate before fetching products from eshop category");
                return [];
            }
        }

        // Формируем URL для получения товаров
        // Первая страница (page=1) использует load-more=0, вторая (page=2) - load-more=1 и т.д.
        $url = $this->baseUrl . '/eshop.php?cat_id=' . $catId;
        $loadMore = $page - 1; // page=1 -> load-more=0, page=2 -> load-more=1
        $url .= '&load-more=' . $loadMore;

        $html = $this->fetchPage($url);
        if (!$html) {
            Log::warning("Failed to fetch eshop products page", ['url' => $url]);
            return [];
        }

        $xpath = $this->parseHtml($html);
        if (!$xpath) {
            Log::warning("Failed to parse HTML for eshop products");
            return [];
        }

        $products = [];

        // Ищем товары на странице
        // Обычно товары находятся в элементах с классом product или item
        $productSelectors = [
            "//div[contains(@class, 'product')]//a[@href]",
            "//div[contains(@class, 'item')]//a[@href]",
            "//div[contains(@class, 'product-item')]//a[@href]",
            "//a[contains(@href, '-d')]",
            "//div[@data-product-id]//a",
        ];

        foreach ($productSelectors as $selector) {
            $nodes = $xpath->query($selector);
            foreach ($nodes as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);

                if (!$name || strlen($name) < 2) {
                    continue;
                }

                // Формируем полный URL
                if (str_starts_with($href, 'http')) {
                    $fullUrl = $href;
                } else {
                    $fullUrl = $this->baseUrl . '/' . ltrim($href, '/');
                }

                // Проверяем, что это ссылка на товар (обычно содержит -d123456/)
                if (preg_match('/-d\d+\/$/', $fullUrl) || stripos($fullUrl, 'essensworld.ru') !== false) {
                    $normalizedUrl = rtrim($fullUrl, '/');
                    
                    // Ищем изображение товара ВНУТРИ ссылки на товар (как в примере: <a><img></a>)
                    $imageUrl = null;
                    
                    // Сначала ищем изображение непосредственно внутри ссылки
                    $imageNode = $xpath->query(".//img[@src]", $node)->item(0);
                    
                    // Если не нашли внутри ссылки, ищем в родительском элементе ссылки (контейнер с классом relative)
                    if (!$imageNode) {
                        $parent = $node->parentNode;
                        if ($parent && $parent->getAttribute('class') && strpos($parent->getAttribute('class'), 'relative') !== false) {
                            $imageNode = $xpath->query(".//img[@src]", $parent)->item(0);
                        }
                    }
                    
                    // Если все еще не нашли, ищем в родительских элементах (fallback)
                    if (!$imageNode) {
                        $searchNode = $node->parentNode;
                        $maxDepth = 3;
                        $depth = 0;
                        while ($searchNode && $depth < $maxDepth) {
                            // Ищем изображения с классом product_img или product_img_w (основные изображения товаров)
                            $imageNodes = $xpath->query(".//img[contains(@class, 'product_img') or contains(@class, 'product')]", $searchNode);
                            if ($imageNodes->length > 0) {
                                $imageNode = $imageNodes->item(0);
                                break;
                            }
                            // Если не нашли по классу, ищем любое изображение
                            $imageNodes = $xpath->query(".//img[@src]", $searchNode);
                            if ($imageNodes->length > 0) {
                                $imageNode = $imageNodes->item(0);
                                break;
                            }
                            $searchNode = $searchNode->parentNode;
                            $depth++;
                        }
                    }
                    
                    if ($imageNode) {
                        $imgSrc = $imageNode->getAttribute('src') ?: $imageNode->getAttribute('data-src');
                        if ($imgSrc) {
                            // Пропускаем служебные изображения
                            if (stripos($imgSrc, 'coupon') === false && 
                                stripos($imgSrc, 'icon') === false && 
                                stripos($imgSrc, 'logo') === false &&
                                stripos($imgSrc, 'placeholder') === false &&
                                stripos($imgSrc, 'no-image') === false) {
                                
                                // Если уже полный URL с протоколом
                                if (str_starts_with($imgSrc, 'http://') || str_starts_with($imgSrc, 'https://')) {
                                    $imageUrl = $imgSrc;
                                } 
                                // Если начинается с // (протокол-относительный URL)
                                elseif (str_starts_with($imgSrc, '//')) {
                                    $imageUrl = 'https:' . $imgSrc;
                                }
                                // Если путь начинается с /static. или содержит домен в пути
                                elseif (str_starts_with($imgSrc, '/static.') || preg_match('/^\/[^\/]+\.(com|ru|net|org)/', $imgSrc)) {
                                    // Формируем правильный URL: https://static.essensworld.com/...
                                    if (preg_match('/^\/([^\/]+\.(com|ru|net|org))(.+)$/', $imgSrc, $matches)) {
                                        $imageUrl = 'https://' . $matches[1] . $matches[3];
                                    } else {
                                        $imageUrl = 'https://www' . $imgSrc;
                                    }
                                }
                                // Если начинается с /, но не содержит домен в пути
                                elseif (str_starts_with($imgSrc, '/')) {
                                    $imageUrl = $this->baseUrl . $imgSrc;
                                }
                                // Относительный путь
                                else {
                                    $imageUrl = $this->baseUrl . '/' . ltrim($imgSrc, '/');
                                }
                            }
                        }
                    }
                    
                    // Ищем цену товара (ищем в родительском элементе и его родителях)
                    $price = null;
                    $priceText = null;
                    $searchNode = $node->parentNode;
                    $depth = 0;
                    while ($searchNode && $depth < $maxDepth) {
                        $priceNodes = $xpath->query(".//*[contains(@class, 'price')] | .//*[contains(@class, 'cost')] | .//span[contains(text(), 'руб')] | .//span[contains(text(), '₽')] | .//*[contains(text(), 'руб')]", $searchNode);
                        foreach ($priceNodes as $priceNode) {
                            $priceText = trim($priceNode->textContent);
                            // Ищем число с возможными пробелами и запятыми
                            if (preg_match('/(\d+[\s,.]?\d*)/', $priceText, $matches)) {
                                $price = (float)str_replace([' ', ','], '', $matches[1]);
                                if ($price > 0) {
                                    break 2; // Выходим из обоих циклов
                                }
                            }
                        }
                        $searchNode = $searchNode->parentNode;
                        $depth++;
                    }
                    
                    // Ищем артикул/SKU из URL (извлекаем -d{id})
                    $sku = null;
                    if (preg_match('/-d(\d+)/', $normalizedUrl, $matches)) {
                        $sku = $matches[1];
                    }
                    
                    $products[] = [
                        'name' => $name,
                        'url' => $normalizedUrl,
                        'image' => $imageUrl,
                        'price' => $price,
                        'price_text' => $priceText,
                        'sku' => $sku,
                    ];
                }
            }

            if (count($products) > 0) {
                break; // Если нашли товары, прекращаем поиск
            }
        }

        // Если не нашли через селекторы, пробуем найти все ссылки с паттерном товара
        if (count($products) === 0) {
            $allLinks = $xpath->query("//a[@href]");
            foreach ($allLinks as $node) {
                $href = $node->getAttribute('href');
                $name = trim($node->textContent);

                if (!$name || strlen($name) < 2) {
                    continue;
                }

                $fullUrl = str_starts_with($href, 'http') ? $href : $this->baseUrl . '/' . ltrim($href, '/');

                // Проверяем, что это товар (паттерн -d123456/)
                if (preg_match('/-d\d+\/$/', $fullUrl) && stripos($fullUrl, 'essensworld.ru') !== false) {
                    $normalizedUrl = rtrim($fullUrl, '/');
                    
                    // Ищем изображение товара ВНУТРИ ссылки на товар (как в примере: <a><img></a>)
                    $imageUrl = null;
                    
                    // Сначала ищем изображение непосредственно внутри ссылки
                    $imageNode = $xpath->query(".//img[@src]", $node)->item(0);
                    
                    // Если не нашли внутри ссылки, ищем в родительском элементе ссылки (контейнер с классом relative)
                    if (!$imageNode) {
                        $parent = $node->parentNode;
                        if ($parent && $parent->getAttribute('class') && strpos($parent->getAttribute('class'), 'relative') !== false) {
                            $imageNode = $xpath->query(".//img[@src]", $parent)->item(0);
                        }
                    }
                    
                    // Если все еще не нашли, ищем в родительских элементах (fallback)
                    if (!$imageNode) {
                        $searchNode = $node->parentNode;
                        $maxDepth = 3;
                        $depth = 0;
                        while ($searchNode && $depth < $maxDepth) {
                            // Ищем изображения с классом product_img или product_img_w (основные изображения товаров)
                            $imageNodes = $xpath->query(".//img[contains(@class, 'product_img') or contains(@class, 'product')]", $searchNode);
                            if ($imageNodes->length > 0) {
                                $imageNode = $imageNodes->item(0);
                                break;
                            }
                            // Если не нашли по классу, ищем любое изображение
                            $imageNodes = $xpath->query(".//img[@src]", $searchNode);
                            if ($imageNodes->length > 0) {
                                $imageNode = $imageNodes->item(0);
                                break;
                            }
                            $searchNode = $searchNode->parentNode;
                            $depth++;
                        }
                    }
                    
                    if ($imageNode) {
                        $imgSrc = $imageNode->getAttribute('src') ?: $imageNode->getAttribute('data-src');
                        if ($imgSrc) {
                            // Пропускаем служебные изображения
                            if (stripos($imgSrc, 'coupon') === false && 
                                stripos($imgSrc, 'icon') === false && 
                                stripos($imgSrc, 'logo') === false &&
                                stripos($imgSrc, 'placeholder') === false &&
                                stripos($imgSrc, 'no-image') === false) {
                                
                                // Если уже полный URL с протоколом
                                if (str_starts_with($imgSrc, 'http://') || str_starts_with($imgSrc, 'https://')) {
                                    $imageUrl = $imgSrc;
                                } 
                                // Если начинается с // (протокол-относительный URL)
                                elseif (str_starts_with($imgSrc, '//')) {
                                    $imageUrl = 'https:' . $imgSrc;
                                }
                                // Если путь начинается с /static. или содержит домен в пути
                                elseif (str_starts_with($imgSrc, '/static.') || preg_match('/^\/[^\/]+\.(com|ru|net|org)/', $imgSrc)) {
                                    // Формируем правильный URL: https://static.essensworld.com/...
                                    if (preg_match('/^\/([^\/]+\.(com|ru|net|org))(.+)$/', $imgSrc, $matches)) {
                                        $imageUrl = 'https://' . $matches[1] . $matches[3];
                                    } else {
                                        $imageUrl = 'https://www' . $imgSrc;
                                    }
                                }
                                // Если начинается с /, но не содержит домен в пути
                                elseif (str_starts_with($imgSrc, '/')) {
                                    $imageUrl = $this->baseUrl . $imgSrc;
                                }
                                // Относительный путь
                                else {
                                    $imageUrl = $this->baseUrl . '/' . ltrim($imgSrc, '/');
                                }
                            }
                        }
                    }
                    
                    // Ищем цену товара (ищем в родительском элементе и его родителях)
                    $price = null;
                    $priceText = null;
                    $searchNode = $node->parentNode;
                    $depth = 0;
                    while ($searchNode && $depth < $maxDepth) {
                        $priceNodes = $xpath->query(".//*[contains(@class, 'price')] | .//*[contains(@class, 'cost')] | .//span[contains(text(), 'руб')] | .//span[contains(text(), '₽')] | .//*[contains(text(), 'руб')]", $searchNode);
                        foreach ($priceNodes as $priceNode) {
                            $priceText = trim($priceNode->textContent);
                            // Ищем число с возможными пробелами и запятыми
                            if (preg_match('/(\d+[\s,.]?\d*)/', $priceText, $matches)) {
                                $price = (float)str_replace([' ', ','], '', $matches[1]);
                                if ($price > 0) {
                                    break 2; // Выходим из обоих циклов
                                }
                            }
                        }
                        $searchNode = $searchNode->parentNode;
                        $depth++;
                    }
                    
                    // Ищем артикул/SKU из URL
                    $sku = null;
                    if (preg_match('/-d(\d+)/', $normalizedUrl, $matches)) {
                        $sku = $matches[1];
                    }
                    
                    $products[] = [
                        'name' => $name,
                        'url' => $normalizedUrl,
                        'image' => $imageUrl,
                        'price' => $price,
                        'price_text' => $priceText,
                        'sku' => $sku,
                    ];
                }
            }
        }

        // Удаляем дубликаты по URL
        $uniqueProducts = [];
        $seenUrls = [];
        foreach ($products as $product) {
            if (!in_array($product['url'], $seenUrls)) {
                $uniqueProducts[] = $product;
                $seenUrls[] = $product['url'];
            }
        }

        Log::info("Eshop products found", [
            'cat_id' => $catId,
            'page' => $page,
            'total' => count($uniqueProducts),
        ]);

        return $uniqueProducts;
    }
}

