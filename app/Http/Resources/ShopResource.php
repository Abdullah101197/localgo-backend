<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
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
            'name' => $this->name,
            'category' => $this->category,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'delivery_radius' => $this->delivery_radius,
            'is_verified' => (bool) $this->is_verified,
            'distance' => $this->when(isset($this->distance), round($this->distance, 2)),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'created_at' => $this->created_at,
        ];
    }
}
