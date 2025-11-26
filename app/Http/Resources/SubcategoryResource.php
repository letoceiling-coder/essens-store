<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_active' => $this->is_active ?? true,
            'position' => $this->position ?? 0,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'products_count' => $this->whenCounted('products'),
        ];
    }
}
