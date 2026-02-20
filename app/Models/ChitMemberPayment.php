<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChitMemberPayment extends Model
{
    use HasFactory;

    protected $table = 'chit_member_slot_payments';

    public const STATUS_DUE = 1;
    public const STATUS_NOT_PAID = 2;
    public const STATUS_PAID = 3;

    public const STATUS_MAP = [
        self::STATUS_NOT_PAID => ['key' => 'not_paid', 'label' => 'Not Paid'],
        self::STATUS_DUE => ['key' => 'due', 'label' => 'Due'],
        self::STATUS_PAID => ['key' => 'paid', 'label' => 'Paid'],
    ];

    protected $fillable = [
        'chit_id',
        'chit_member_slot_id',
        'month_number',
        'expected_amount',
        'paid_amount',
        'due_amount',
        'extra_paid_amount',
        'status_code',
        'is_paid',
        'paid_at',
        'recorded_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'chit_id' => 'integer',
            'chit_member_slot_id' => 'integer',
            'month_number' => 'integer',
            'expected_amount' => 'integer',
            'paid_amount' => 'integer',
            'due_amount' => 'integer',
            'extra_paid_amount' => 'integer',
            'status_code' => 'integer',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'recorded_by' => 'integer',
        ];
    }

    public static function statusOptionsByKey(): array
    {
        $options = [];

        foreach (self::STATUS_MAP as $code => $status) {
            $options[$status['key']] = [
                'code' => $code,
                'label' => $status['label'],
            ];
        }

        return $options;
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

    public function chit(): BelongsTo
    {
        return $this->belongsTo(Chit::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(ChitMemberSlot::class, 'chit_member_slot_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function getStatusKeyAttribute(): string
    {
        return self::statusFromCode($this->status_code)['key'] ?? 'not_paid';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusFromCode($this->status_code)['label'] ?? 'Not Paid';
    }
}
