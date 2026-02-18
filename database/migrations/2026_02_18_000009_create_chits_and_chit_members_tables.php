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
        Schema::create('chits', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('plan_code');
            $table->unsignedTinyInteger('chit_type_code');
            $table->unsignedInteger('total_amount');
            $table->unsignedSmallInteger('duration_months');
            $table->unsignedTinyInteger('member_limit')->default(20);
            $table->unsignedInteger('monthly_amount');
            $table->unsignedTinyInteger('total_slots_assigned')->default(0);
            $table->unsignedTinyInteger('status_code')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['plan_code', 'chit_type_code']);
            $table->index('created_at');
        });

        Schema::create('chit_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chit_id')->constrained('chits')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_count');

            $table->unique(['chit_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_members');
        Schema::dropIfExists('chits');
    }
};
