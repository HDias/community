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
        Schema::table('contributions', function (Blueprint $table) {
            $table->string('donor_type')->nullable()->after('type');
            $table->string('donor_name')->nullable()->after('donor_type');
            $table->string('donor_document')->nullable()->after('donor_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            $table->dropColumn(['donor_type', 'donor_name', 'donor_document']);
        });
    }
};
