<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_obligations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind');
            $table->string('direction');
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->string('frequency');
            $table->smallInteger('interval')->default(1);
            $table->date('anchor_date');
            $table->smallInteger('day_of_month')->nullable();
            $table->smallInteger('secondary_day_of_month')->nullable();
            $table->smallInteger('day_of_week')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('autopay')->default(false);
            $table->boolean('is_active')->default(true);
            $table->date('last_materialized_through')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'kind']);
            $table->index('anchor_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_obligations');
    }
};
