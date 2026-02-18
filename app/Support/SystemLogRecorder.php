<?php

namespace App\Support;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class SystemLogRecorder
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function record(
        string $action,
        ?User $actor = null,
        ?User $target = null,
        ?Request $request = null,
        ?string $description = null,
        array $metadata = []
    ): void {
        unset($description, $metadata);

        $request ??= request();
        $actor ??= $request?->user();

        if (! $actor || ! in_array($actor->role, [User::ROLE_ADMIN, User::ROLE_EDITOR], true)) {
            return;
        }

        $actionCode = SystemLog::actionCode($action);
        if ($actionCode === 0) {
            return;
        }

        try {
            SystemLog::query()->create([
                'actor_id' => $actor->id,
                'actor_role_code' => SystemLog::roleCode($actor->role),
                'action_code' => $actionCode,
                'target_user_id' => $target?->id,
                'ip_address' => $request?->ip(),
                'created_at' => now(),
            ]);

            // Keep storage bounded by deleting older records in small periodic batches.
            self::pruneOldRows();
        } catch (Throwable) {
            // Logging must never block the main request flow.
        }
    }

    private static function pruneOldRows(): void
    {
        $retentionDays = (int) env('SYSTEM_LOG_RETENTION_DAYS', 90);
        if ($retentionDays <= 0) {
            return;
        }

        if (random_int(1, 100) !== 1) {
            return;
        }

        SystemLog::query()
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->limit(500)
            ->delete();
    }
}
