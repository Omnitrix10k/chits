<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChitMemberSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'chit_id',
        'chit_member_id',
        'user_id',
        'slot_sequence',
        'display_order',
        'referred_by_name',
        'age',
    ];

    protected function casts(): array
    {
        return [
            'chit_id' => 'integer',
            'chit_member_id' => 'integer',
            'user_id' => 'integer',
            'slot_sequence' => 'integer',
            'display_order' => 'integer',
            'age' => 'integer',
        ];
    }

    public function chit(): BelongsTo
    {
        return $this->belongsTo(Chit::class);
    }

    public function chitMember(): BelongsTo
    {
        return $this->belongsTo(ChitMember::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ChitMemberPayment::class);
    }

    public function getDisplayNameAttribute(): string
    {
        $baseName = trim((string) ($this->user?->name ?? 'Member'));
        $sequence = max(1, (int) $this->slot_sequence);

        return trim($baseName.' '.$sequence);
    }
}
