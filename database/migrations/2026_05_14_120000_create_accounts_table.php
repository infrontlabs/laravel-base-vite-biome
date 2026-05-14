<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind');
            $table->string('subkind')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->bigInteger('current_balance_cents')->default(0);
            $table->bigInteger('available_balance_cents')->nullable();
            $table->timestamp('as_of')->nullable();
            $table->boolean('is_liability')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('manual_only')->default(true);
            $table->unsignedBigInteger('plaid_item_id')->nullable();
            $table->string('plaid_account_id')->nullable();
            $table->string('mask', 4)->nullable();
            $table->boolean('include_in_safe_to_spend')->default(true);
            $table->boolean('include_in_net_worth')->default(true);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index('plaid_item_id');
            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
