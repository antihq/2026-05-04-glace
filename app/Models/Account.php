<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'type', 'credit_limit'])]
class Account extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
        ];
    }

    public function getCreditLimitAttribute(?int $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return number_format($value / 100, 2, '.', '');
    }

    public function setCreditLimitAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['credit_limit'] = null;

            return;
        }

        $this->attributes['credit_limit'] = (int) round(((float) str_replace(',', '', $value)) * 100);
    }

    public function getCreditLimitInCentsAttribute(): ?int
    {
        return $this->attributes['credit_limit'] ?? null;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(Balance::class)->orderByDesc('checked_in_at');
    }
}
