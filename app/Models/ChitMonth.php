<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChitMonth extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 1;
    public const STATUS_OPEN = 2;
    public const STATUS_CLOSED = 3;

    public const STATUS_MAP = [
        self::STATUS_PENDING => ['key' => 'pending', 'label' => 'Pending'],
        self::STATUS_OPEN => ['key' => 'open', 'label' => 'Open'],
        self::STATUS_CLOSED => ['key' => 'closed', 'label' => 'Closed'],
    ];

    public $timestamps = false;

    protected $fillable = [
        'chit_id',
        'month_number',
        'status_code',
        'auction_amount',
        'auction_winner_slot_id',
        'initialized_at',
        'auction_recorded_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'chit_id' => 'integer',
            'month_number' => 'integer',
            'status_code' => 'integer',
            'auction_amount' => 'integer',
            'auction_winner_slot_id' => 'integer',
            'initialized_at' => 'datetime',
            'auction_recorded_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function chit(): BelongsTo
    {
        return $this->belongsTo(Chit::class);
    }

    public function auctionWinnerSlot(): BelongsTo
    {
        return $this->belongsTo(ChitMemberSlot::class, 'auction_winner_slot_id');
    }

    public static function statusCodeFromKey(string $key): ?int
    {
        foreach (self::STATUS_MAP as $code => $status) {
            if ($status['key'] === $key) {
                return $code;
            }
        }

        return null;
    }

    /**
     * @return array{key:string,label:string}|null
     */
    public static function statusFromCode(?int $code): ?array
    {
        if (! $code) {
            return null;
        }

        return self::STATUS_MAP[$code] ?? null;
    }

    public function getStatusKeyAttribute(): string
    {
        return self::statusFromCode($this->status_code)['key'] ?? 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusFromCode($this->status_code)['label'] ?? 'Pending';
    }
}

