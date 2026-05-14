<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->bigInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            $table->date('posted_date')->nullable();
            $table->date('authorized_date')->nullable();
            $table->date('pending_date')->nullable();
            $table->string('description');
            $table->string('merchant_name')->nullable();
            $table->text('raw_description')->nullable();
            $table->string('source');
            $table->string('status');
            $table->string('plaid_transaction_id')->nullable()->unique();
            $table->unsignedBigInteger('plaid_item_id')->nullable();
            $table->string('pending_plaid_transaction_id')->nullable();
            $table->foreignId('merged_into_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->foreignId('merged_from_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->boolean('excluded_from_budget')->default(false);
            $table->text('notes')->nullable();
            $table->json('tags')->nullable();
            $table->timestamps();

            $table->index(['account_id', 'posted_date']);
            $table->index('status');
            $table->index(['category_id', 'posted_date']);
            $table->index('plaid_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
