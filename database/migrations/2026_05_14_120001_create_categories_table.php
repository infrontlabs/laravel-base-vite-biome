<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('group');
            $table->string('plaid_primary')->nullable();
            $table->string('plaid_detailed')->nullable();
            $table->bigInteger('monthly_target_cents')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->index('plaid_primary');
            $table->index(['group', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
