<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Output of the matching engine (FR-C1/FR-C2) — one row per lost/found pair worth surfacing.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lost_item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('found_item_id')->constrained('items')->cascadeOnDelete();
            $table->decimal('confidence_score', 5, 2); // 0.00 - 100.00
            $table->enum('match_status', ['pending', 'confirmed', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['lost_item_id', 'found_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_matches');
    }
};
