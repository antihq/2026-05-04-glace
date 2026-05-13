<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['account_id', 'checkin_id', 'amount', 'checked_in_at'])]
class Balance extends Model
{
    use HasFactory;

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function checkin(): BelongsTo
    {
        return $this->belongsTo(Checkin::class);
    }

    public function getAmountAttribute(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }

    public function setAmountAttribute($value): void
    {
        $this->attributes['amount'] = (int) round(((float) str_replace(',', '', $value)) * 100);
    }

    public function getAmountInCentsAttribute(): int
    {
        return (int) $this->attributes['amount'];
    }

    protected function casts(): array
    {
        return [
            'checked_in_at' => 'datetime',
        ];
    }
}
