<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligation_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheduled_obligation_id')->constrained()->cascadeOnDelete();
            $table->date('due_date');
            $table->bigInteger('expected_amount_cents');
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('expected');
            $table->timestamp('matched_at')->nullable();
            $table->timestamps();

            $table->unique(['scheduled_obligation_id', 'due_date']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_instances');
    }
};
