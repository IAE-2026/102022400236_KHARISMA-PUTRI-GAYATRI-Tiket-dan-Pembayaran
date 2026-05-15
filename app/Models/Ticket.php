<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    protected $fillable = [
        'Cust_Name',
        'route_id',
        'seat_number',
        'status',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'route_id' => 'integer',
        ];
    }

    #[Scope]
    protected function latestFirst(Builder $query): void
    {
        $query->orderByDesc('created_at');
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
}
