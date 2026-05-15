<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'cust_name' => $this->Cust_Name,
            'route_id' => $this->route_id,
            'seat_number' => $this->seat_number,
            'status' => $this->status,
            'price' => (float) $this->price,
            'payment' => PaymentResource::make($this->whenLoaded('payment')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
