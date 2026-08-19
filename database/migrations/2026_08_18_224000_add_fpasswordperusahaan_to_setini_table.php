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
        Schema::table('setini', function (Blueprint $table) {
            if (!Schema::hasColumn('setini', 'fpasswordperusahaan')) {
                $table->text('fpasswordperusahaan')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('setini', function (Blueprint $table) {
            if (Schema::hasColumn('setini', 'fpasswordperusahaan')) {
                $table->dropColumn('fpasswordperusahaan');
            }
        });
    }
};
