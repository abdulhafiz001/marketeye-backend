<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_price_seeds', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->after('effective_date');
            $table->text('error_message')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('error_message');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('external_price_seeds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn(['status', 'error_message', 'approved_at']);
        });
    }
};
