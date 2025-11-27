<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $url = $this->url;
        
        // Если URL относительный (начинается с /), делаем его абсолютным
        if ($url && str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            // Для локальных файлов используем APP_URL
            $baseUrl = rtrim(config('app.url'), '/');
            $url = $baseUrl . $url;
        }
        // Если URL уже абсолютный (начинается с http:// или https://), оставляем как есть
        // Если URL начинается с //, добавляем https:
        elseif ($url && str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'url' => $url,
            'order' => $this->order,
            'is_primary' => $this->is_primary,
        ];
    }
}
