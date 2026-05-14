<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_obligations', function (Blueprint $table) {
            $table->string('cancel_url')->nullable()->after('autopay');
            $table->date('last_reviewed_at')->nullable()->after('cancel_url');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_obligations', function (Blueprint $table) {
            $table->dropColumn(['cancel_url', 'last_reviewed_at']);
        });
    }
};
