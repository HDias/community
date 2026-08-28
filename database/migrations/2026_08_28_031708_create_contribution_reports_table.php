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
        Schema::create('contribution_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->date('reference_month');
            $table->unsignedInteger('total_members');
            $table->unsignedInteger('total_paid');
            $table->unsignedInteger('total_defaulting');
            $table->decimal('total_collected', 10, 2);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['community_id', 'reference_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_reports');
    }
};
