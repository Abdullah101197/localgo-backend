<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'status' => $this->status,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'delivery_address' => $this->delivery_address,
            'created_at' => $this->created_at,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
