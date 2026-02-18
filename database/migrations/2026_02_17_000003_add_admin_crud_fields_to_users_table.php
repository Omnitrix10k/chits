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
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('primary_phone')->nullable()->after('mobile_number');
            $table->string('address', 1000)->nullable()->after('primary_phone');
            $table->string('government_id_path')->nullable()->after('address');
            $table->unique('primary_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_primary_phone_unique');
            $table->dropColumn([
                'first_name',
                'last_name',
                'primary_phone',
                'address',
                'government_id_path',
            ]);
        });
    }
};
