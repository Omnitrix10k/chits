<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    public const ROLE_CODE_MAP = [
        User::ROLE_ADMIN => 1,
        User::ROLE_EDITOR => 2,
    ];

    public const ROLE_NAME_MAP = [
        1 => User::ROLE_ADMIN,
        2 => User::ROLE_EDITOR,
    ];

    public const ACTION_CODE_MAP = [
        'login' => 1,
        'logout' => 2,
        'profile_updated' => 3,
        'profile_deleted' => 4,
        'member_created' => 5,
        'member_updated' => 6,
        'member_deleted' => 7,
        'editor_created' => 8,
        'editor_updated' => 9,
        'editor_deleted' => 10,
    ];

    public const ACTION_LABEL_MAP = [
        1 => 'Login',
        2 => 'Logout',
        3 => 'Profile Updated',
        4 => 'Profile Deleted',
        5 => 'Member Created',
        6 => 'Member Updated',
        7 => 'Member Deleted',
        8 => 'Editor Created',
        9 => 'Editor Updated',
        10 => 'Editor Deleted',
    ];

    public $timestamps = false;

    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_id',
        'actor_role_code',
        'action_code',
        'target_user_id',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    protected function ipAddress(): Attribute
    {
        return Attribute::make(
            get: static function ($value): ?string {
                if (! is_string($value) || $value === '') {
                    return null;
                }

                $decoded = @inet_ntop($value);

                return $decoded === false ? null : $decoded;
            },
            set: static function ($value): ?string {
                if (! is_string($value) || $value === '') {
                    return null;
                }

                $encoded = @inet_pton($value);

                return $encoded === false ? null : $encoded;
            }
        );
    }

    public static function actionCode(string $action): int
    {
        return self::ACTION_CODE_MAP[$action] ?? 0;
    }

    public static function actionName(?int $code): string
    {
        if (! $code) {
            return 'unknown';
        }

        return array_search($code, self::ACTION_CODE_MAP, true) ?: 'unknown';
    }

    public static function actionLabel(?int $code): string
    {
        if (! $code) {
            return 'Unknown';
        }

        return self::ACTION_LABEL_MAP[$code] ?? ucfirst(str_replace('_', ' ', self::actionName($code)));
    }

    /**
     * @return array<string, string>
     */
    public static function actionLabelsByName(): array
    {
        $labels = [];

        foreach (self::ACTION_CODE_MAP as $name => $code) {
            $labels[$name] = self::actionLabel($code);
        }

        return $labels;
    }

    /**
     * @return array<int, string>
     */
    public static function actionNames(): array
    {
        return array_keys(self::ACTION_CODE_MAP);
    }

    public static function roleCode(?string $role): ?int
    {
        if (! $role) {
            return null;
        }

        return self::ROLE_CODE_MAP[$role] ?? null;
    }

    public static function roleName(?int $code): ?string
    {
        if (! $code) {
            return null;
        }

        return self::ROLE_NAME_MAP[$code] ?? null;
    }
}
