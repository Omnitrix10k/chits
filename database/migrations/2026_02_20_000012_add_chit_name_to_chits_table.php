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
            if (! Schema::hasColumn('chits', 'chit_name')) {
                $table->string('chit_name', 255)->nullable()->after('id');
                $table->index('chit_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chits', function (Blueprint $table): void {
            if (Schema::hasColumn('chits', 'chit_name')) {
                $table->dropIndex(['chit_name']);
                $table->dropColumn('chit_name');
            }
        });
    }
};

