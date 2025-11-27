<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AdminMenuController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\v1\FolderController;
use App\Http\Controllers\Api\v1\MediaController;
use App\Http\Controllers\Api\Store\CategoryController;
use App\Http\Controllers\Api\Store\ProductController;
use App\Http\Controllers\Api\Admin\ParsingController;
use App\Http\Controllers\Api\Admin\TelegramBotController;
use App\Http\Controllers\Api\DeployController;
use Illuminate\Support\Facades\Route;

// Telegram webhook endpoint (публичный, для получения обновлений от Telegram)
Route::post('/telegram/webhook/{token}', [\App\Http\Controllers\Api\TelegramWebhookController::class, 'handle']);

// Deploy endpoint (защищён секретным ключом в заголовке Deploy-Secret)
Route::post('/deploy', [DeployController::class, 'deploy']);

// Version endpoint (публичный, для проверки версии на сервере)
Route::get('/deploy/version', [DeployController::class, 'version']);

// Публичные роуты
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

// Публичные роуты магазина
Route::prefix('store')->group(function () {
    // Категории
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::get('/categories/slug/{slug}', [CategoryController::class, 'showBySlug']);
    
    // Товары
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::get('/products/sku/{sku}', [ProductController::class, 'showBySku']);
});

// Защищённые роуты
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'user']);
    
    // Меню
    Route::get('/admin/menu', [AdminMenuController::class, 'index']);
    
    // Уведомления
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/all', [NotificationController::class, 'all']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    
    // Media API (v1)
    Route::prefix('v1')->group(function () {
        // Folders
        Route::get('folders/tree/all', [FolderController::class, 'tree'])->name('folders.tree');
        Route::post('folders/update-positions', [FolderController::class, 'updatePositions'])->name('folders.update-positions');
        Route::post('folders/{id}/restore', [FolderController::class, 'restore'])->name('folders.restore');
        Route::apiResource('folders', FolderController::class);
        
        // Media
        Route::post('media/{id}/restore', [MediaController::class, 'restore'])->name('media.restore');
        Route::delete('media/trash/empty', [MediaController::class, 'emptyTrash'])->name('media.trash.empty');
        Route::apiResource('media', MediaController::class);
        
        // Admin only routes (Roles and Users management)
        Route::middleware('admin')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('users', UserController::class);
        });
    });

    // Admin routes for store management
    Route::prefix('admin')->group(function () {
        Route::apiResource('categories', \App\Http\Controllers\Api\Admin\CategoryController::class);
        Route::apiResource('subcategories', \App\Http\Controllers\Api\Admin\SubcategoryController::class);
        Route::apiResource('products', \App\Http\Controllers\Api\Admin\ProductController::class);
        Route::apiResource('promotions', \App\Http\Controllers\Api\Admin\PromotionController::class);
        
        // Parsing routes
        Route::prefix('parsing')->group(function () {
            Route::get('/check-availability', [ParsingController::class, 'checkAvailability']);
            Route::get('/check-authentication', [ParsingController::class, 'checkAuthentication']);
            Route::get('/categories', [ParsingController::class, 'getCategories']);
            Route::post('/category-products', [ParsingController::class, 'getProductsFromCategory']);
            Route::post('/product', [ParsingController::class, 'parseProduct']);
            // Eshop parsing routes
            Route::get('/eshop/categories', [ParsingController::class, 'getEshopCategories']);
            Route::post('/eshop/category-products', [ParsingController::class, 'getProductsFromEshopCategory']);
            Route::post('/eshop/parse-and-save-products', [ParsingController::class, 'parseAndSaveProducts']);
            Route::get('/eshop/debug-html', [ParsingController::class, 'getEshopPageHtml']); // Для отладки
        });

        // Product Parsing Queue routes
        Route::apiResource('parsing-queue', \App\Http\Controllers\Api\Admin\ProductParsingQueueController::class);

        // Telegram Bot routes
        Route::prefix('telegram-bot')->group(function () {
            Route::apiResource('bots', TelegramBotController::class);
            
            // Bot info and updates
            Route::get('bots/{id}/info', [TelegramBotController::class, 'getBotInfo']);
            Route::get('bots/{id}/updates', [TelegramBotController::class, 'getUpdates']);
            
            // Commands
            Route::post('bots/{id}/commands', [TelegramBotController::class, 'setCommands']);
            Route::get('bots/{id}/commands', [TelegramBotController::class, 'getCommands']);
            Route::delete('bots/{id}/commands', [TelegramBotController::class, 'deleteCommands']);
            
            // Description
            Route::post('bots/{id}/description', [TelegramBotController::class, 'setDescription']);
            Route::get('bots/{id}/description', [TelegramBotController::class, 'getDescription']);
            
            // Short description
            Route::post('bots/{id}/short-description', [TelegramBotController::class, 'setShortDescription']);
            Route::get('bots/{id}/short-description', [TelegramBotController::class, 'getShortDescription']);
            
            // Name
            Route::post('bots/{id}/name', [TelegramBotController::class, 'setName']);
            Route::get('bots/{id}/name', [TelegramBotController::class, 'getName']);
            
            // Photo
            Route::post('bots/{id}/photo', [TelegramBotController::class, 'setPhoto']);
            Route::delete('bots/{id}/photo', [TelegramBotController::class, 'deletePhoto']);
            
            // Menu button
            Route::post('bots/{id}/menu-button', [TelegramBotController::class, 'setMenuButton']);
            Route::get('bots/{id}/menu-button', [TelegramBotController::class, 'getMenuButton']);
            
            // Webhook
            Route::post('bots/{id}/webhook', [TelegramBotController::class, 'setWebhook']);
            Route::delete('bots/{id}/webhook', [TelegramBotController::class, 'deleteWebhook']);
            Route::get('bots/{id}/webhook', [TelegramBotController::class, 'getWebhookInfo']);
            Route::post('bots/{id}/webhook/test', [TelegramBotController::class, 'testWebhook']);
        });
    });
});

