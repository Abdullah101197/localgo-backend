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
            'payment_status' => $this->payment_status,
            'delivery_address' => $this->delivery_address,
            'created_at' => $this->created_at,
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'phone' => $this->customer->phone, // Ensure phone is in User model or related
                ];
            }),
            'rider' => $this->whenLoaded('rider', function () {
                return [
                    'id' => $this->rider->id,
                    'name' => $this->rider->user->name,
                    'phone' => $this->rider->user->phone,
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'unread_messages_count' => $this->whenCounted('messages'),
        ];
    }
}
