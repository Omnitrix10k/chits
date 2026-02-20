<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chits', function (Blueprint $table): void {
            $table->unsignedSmallInteger('current_month')->default(1)->after('duration_months');
            $table->index('current_month');
        });

        Schema::create('chit_member_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chit_id')->constrained('chits')->cascadeOnDelete();
            $table->foreignId('chit_member_id')->nullable()->constrained('chit_members')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_sequence');
            $table->unsignedTinyInteger('display_order');
            $table->string('referred_by_name')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->timestamps();

            $table->unique(['chit_id', 'display_order']);
            $table->unique(['chit_id', 'user_id', 'slot_sequence']);
            $table->index('user_id');
        });

        Schema::create('chit_member_slot_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chit_id')->constrained('chits')->cascadeOnDelete();
            $table->foreignId('chit_member_slot_id')->constrained('chit_member_slots')->cascadeOnDelete();
            $table->unsignedSmallInteger('month_number');
            $table->unsignedInteger('expected_amount');
            $table->unsignedInteger('paid_amount')->default(0);
            $table->unsignedInteger('due_amount')->default(0);
            $table->unsignedInteger('extra_paid_amount')->default(0);
            $table->unsignedTinyInteger('status_code')->default(1);
            $table->boolean('is_paid')->default(false);
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->unique(['chit_member_slot_id', 'month_number'], 'chit_slot_month_unique');
            $table->index(['chit_id', 'month_number']);
            $table->index(['status_code', 'is_paid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_member_slot_payments');
        Schema::dropIfExists('chit_member_slots');

        Schema::table('chits', function (Blueprint $table): void {
            $table->dropIndex(['current_month']);
            $table->dropColumn('current_month');
        });
    }
};
