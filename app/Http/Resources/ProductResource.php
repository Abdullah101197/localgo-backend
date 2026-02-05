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
            'shop_id' => $this->shop_id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'category' => $this->category,
            'image_url' => $this->image_url,
            'is_active' => (bool) $this->is_active,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'distance' => $this->when(isset($this->distance), round($this->distance, 2)),
        ];
    }
}
