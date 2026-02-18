<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chit extends Model
{
    use HasFactory;

    public const MEMBER_LIMIT = 20;
    public const MAX_REPEAT_PER_MEMBER = 9;

    public const PLAN_MAP = [
        1 => ['key' => 'one_lakh', 'label' => 'One Lakh Chit', 'amount' => 100000],
        2 => ['key' => 'two_lakh', 'label' => 'Two Lakh Chit', 'amount' => 200000],
        3 => ['key' => 'three_lakh', 'label' => 'Three Lakh Chit', 'amount' => 300000],
        4 => ['key' => 'five_lakh', 'label' => 'Five Lakh Chit', 'amount' => 500000],
        5 => ['key' => 'ten_lakh', 'label' => 'Ten Lakh Chit', 'amount' => 1000000],
    ];

    public const TYPE_MAP = [
        1 => ['key' => 'auction', 'label' => 'Auction'],
        2 => ['key' => 'fixed', 'label' => 'Fixed'],
    ];

    public const STATUS_MAP = [
        1 => ['key' => 'active', 'label' => 'Active'],
    ];

    protected $fillable = [
        'plan_code',
        'chit_type_code',
        'total_amount',
        'duration_months',
        'member_limit',
        'monthly_amount',
        'total_slots_assigned',
        'status_code',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'plan_code' => 'integer',
            'chit_type_code' => 'integer',
            'total_amount' => 'integer',
            'duration_months' => 'integer',
            'member_limit' => 'integer',
            'monthly_amount' => 'integer',
            'total_slots_assigned' => 'integer',
            'status_code' => 'integer',
        ];
    }

    public static function planOptionsByKey(): array
    {
        $options = [];

        foreach (self::PLAN_MAP as $code => $item) {
            $options[$item['key']] = [
                'code' => $code,
                'label' => $item['label'],
                'amount' => $item['amount'],
            ];
        }

        return $options;
    }

    public static function planCodeFromKey(string $key): ?int
    {
        foreach (self::PLAN_MAP as $code => $item) {
            if ($item['key'] === $key) {
                return $code;
            }
        }

        return null;
    }

    public static function planFromCode(?int $code): ?array
    {
        if (! $code) {
            return null;
        }

        return self::PLAN_MAP[$code] ?? null;
    }

    public static function typeCodeFromKey(string $key): ?int
    {
        foreach (self::TYPE_MAP as $code => $item) {
            if ($item['key'] === $key) {
                return $code;
            }
        }

        return null;
    }

    public static function typeFromCode(?int $code): ?array
    {
        if (! $code) {
            return null;
        }

        return self::TYPE_MAP[$code] ?? null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function memberSlots(): HasMany
    {
        return $this->hasMany(ChitMember::class);
    }

    public function getPlanKeyAttribute(): ?string
    {
        return self::planFromCode($this->plan_code)['key'] ?? null;
    }

    public function getPlanLabelAttribute(): string
    {
        return self::planFromCode($this->plan_code)['label'] ?? 'Unknown Plan';
    }

    public function getTypeKeyAttribute(): ?string
    {
        return self::typeFromCode($this->chit_type_code)['key'] ?? null;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeFromCode($this->chit_type_code)['label'] ?? 'Unknown Type';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_MAP[$this->status_code]['label'] ?? 'Unknown';
    }
}
