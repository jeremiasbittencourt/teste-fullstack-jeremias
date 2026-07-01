<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderStatusLog extends Model
{
    /** @use HasFactory<\Database\Factories\OrderStatusLogFactory> */
    use HasFactory;

    // A tabela só tem created_at (auditoria imutável, sem updated_at).
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id',
        'previous_status',
        'new_status',
        'changed_by',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
