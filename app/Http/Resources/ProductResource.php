<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'subcategory_id' => $this->subcategory_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'type' => $this->type,
            'gender_target' => $this->gender_target,
            'volume' => $this->volume,
            'price' => (float) $this->price,
            'old_price' => $this->old_price ? (float) $this->old_price : null,
            'discounted_price' => $this->discounted_price ? (float) $this->discounted_price : null,
            'recommended_price' => $this->recommended_price ? (float) $this->recommended_price : null,
            'currency' => $this->currency,
            'in_stock' => $this->in_stock,
            'stock_qty' => $this->stock_qty,
            'description' => $this->description,
            'tags' => $this->tags ?? [],
            'subcategory' => new SubcategoryResource($this->whenLoaded('subcategory')),
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
            'primary_image' => new ProductImageResource($this->whenLoaded('primaryImage')),
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
            'promotions' => PromotionResource::collection($this->whenLoaded('promotions')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
