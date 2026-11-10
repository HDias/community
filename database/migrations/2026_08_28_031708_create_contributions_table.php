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
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->date('reference_month');
            $table->date('monthly_key')->nullable();
            $table->decimal('amount', 10, 2);
            $table->foreignId('registered_by')->constrained('users')->restrictOnDelete();
            $table->string('type');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['community_id', 'user_id', 'monthly_key'], 'contributions_monthly_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
