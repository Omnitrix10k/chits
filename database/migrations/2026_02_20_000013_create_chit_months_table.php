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
        Schema::create('chit_months', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chit_id')->constrained('chits')->cascadeOnDelete();
            $table->unsignedSmallInteger('month_number');
            $table->unsignedTinyInteger('status_code')->default(1);
            $table->unsignedInteger('auction_amount')->nullable();
            $table->foreignId('auction_winner_slot_id')->nullable()->constrained('chit_member_slots')->nullOnDelete();
            $table->timestamp('initialized_at')->nullable();
            $table->timestamp('auction_recorded_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->unique(['chit_id', 'month_number']);
            $table->index(['chit_id', 'status_code']);
            $table->index('auction_winner_slot_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chit_months');
    }
};

