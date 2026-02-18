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
        Schema::table('users', function (Blueprint $table) {
            $table->string('family_name')->nullable()->after('government_id_path');
            $table->string('family_government_id')->nullable()->after('family_name');
            $table->string('family_relation')->nullable()->after('family_government_id');
            $table->string('family_phone_number')->nullable()->after('family_relation');
            $table->string('family_cheque_number')->nullable()->after('family_phone_number');
            $table->string('family_bank_name')->nullable()->after('family_cheque_number');
            $table->string('family_address', 1000)->nullable()->after('family_bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'family_name',
                'family_government_id',
                'family_relation',
                'family_phone_number',
                'family_cheque_number',
                'family_bank_name',
                'family_address',
            ]);
        });
    }
};
