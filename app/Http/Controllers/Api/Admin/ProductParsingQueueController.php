<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductParsingQueue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ProductParsingQueueController extends Controller
{
    /**
     * Получить список товаров в очереди
     */
    public function index(Request $request)
    {
        $query = ProductParsingQueue::with(['product', 'user'])
            ->orderBy('created_at', 'desc');

        // Фильтрация по статусу
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Поиск
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('product_url', 'like', "%{$search}%")
                  ->orWhere('product_name', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Добавить товар в очередь парсинга
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_url' => 'required|url',
            'product_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Проверяем, не существует ли уже такой URL
        $existing = ProductParsingQueue::where('product_url', $request->product_url)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Этот товар уже находится в очереди на парсинг',
            ], 409);
        }

        $queueItem = ProductParsingQueue::create([
            'product_url' => $request->product_url,
            'product_name' => $request->product_name,
            'status' => 'pending',
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Товар добавлен в очередь парсинга',
            'data' => $queueItem->load('user'),
        ], 201);
    }

    /**
     * Получить информацию о товаре в очереди
     */
    public function show($id)
    {
        $queueItem = ProductParsingQueue::with(['product', 'user'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $queueItem,
        ]);
    }

    /**
     * Удалить товар из очереди
     */
    public function destroy($id)
    {
        $queueItem = ProductParsingQueue::findOrFail($id);

        // Можно удалять только товары со статусом pending или failed
        if (!in_array($queueItem->status, ['pending', 'failed'])) {
            return response()->json([
                'success' => false,
                'message' => 'Нельзя удалить товар со статусом ' . $queueItem->status,
            ], 400);
        }

        $queueItem->delete();

        return response()->json([
            'success' => true,
            'message' => 'Товар удален из очереди',
        ]);
    }
}
