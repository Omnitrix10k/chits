<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('system_logs')) {
            return;
        }

        if (Schema::hasColumn('system_logs', 'action_code')) {
            return;
        }

        Schema::dropIfExists('system_logs_compact');

        Schema::create('system_logs_compact', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedTinyInteger('actor_role_code')->nullable();
            $table->unsignedTinyInteger('action_code');
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->binary('ip_address', 16)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_role_code', 'action_code', 'created_at'], 'system_logs_compact_role_action_created_idx');
            $table->index('actor_id', 'system_logs_compact_actor_idx');
            $table->index('target_user_id', 'system_logs_compact_target_idx');
        });

        $actionCodeMap = [
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

        $roleCodeMap = [
            'admin' => 1,
            'editor' => 2,
        ];

        DB::table('system_logs')
            ->select(['id', 'actor_id', 'actor_role', 'action', 'target_id', 'ip_address', 'created_at'])
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($actionCodeMap, $roleCodeMap): void {
                $payload = [];

                foreach ($rows as $row) {
                    $payload[] = [
                        'id' => $row->id,
                        'actor_id' => $row->actor_id,
                        'actor_role_code' => $roleCodeMap[$row->actor_role] ?? null,
                        'action_code' => $actionCodeMap[$row->action] ?? 0,
                        'target_user_id' => $row->target_id,
                        'ip_address' => $this->toBinaryIp($row->ip_address),
                        'created_at' => $row->created_at ?: now(),
                    ];
                }

                if ($payload !== []) {
                    DB::table('system_logs_compact')->insert($payload);
                }
            });

        Schema::drop('system_logs');
        Schema::rename('system_logs_compact', 'system_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('system_logs')) {
            return;
        }

        if (Schema::hasColumn('system_logs', 'action')) {
            return;
        }

        Schema::drop('system_logs');

        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name');
            $table->string('actor_role');
            $table->string('action');
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('target_name')->nullable();
            $table->string('target_role')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['actor_role', 'action']);
            $table->index(['target_type', 'target_id']);
            $table->index('created_at');
        });
    }

    private function toBinaryIp(?string $ipAddress): ?string
    {
        if (! $ipAddress) {
            return null;
        }

        $binaryIp = @inet_pton($ipAddress);

        return $binaryIp === false ? null : $binaryIp;
    }
};
