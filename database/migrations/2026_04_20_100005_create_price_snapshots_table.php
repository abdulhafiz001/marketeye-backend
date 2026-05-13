<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('market_id')->constrained()->cascadeOnDelete();
            $table->decimal('avg_price', 10, 2);
            $table->decimal('min_price', 10, 2);
            $table->decimal('max_price', 10, 2);
            $table->unsignedInteger('submission_count')->default(0);
            $table->date('snapshot_date');
            $table->boolean('low_confidence')->default(false);
            $table->string('snapshot_source', 32)->default('submission_aggregate');
            $table->timestamps();

            $table->unique(['product_id', 'market_id', 'snapshot_date']);
            $table->index(['product_id', 'market_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_snapshots');
    }
};
