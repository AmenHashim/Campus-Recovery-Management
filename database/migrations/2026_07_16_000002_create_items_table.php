<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// One table for both lost and found reports (FR-B1, FR-B2) — the `type` column distinguishes them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            // Reporter is EITHER a registered user OR a guest — never both, never neither.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('guest_reporter_id')->nullable()->constrained('guest_reporters')->nullOnDelete();
            $table->foreignId('filed_by')->nullable()->constrained('users')->nullOnDelete(); // officer who typed it in on a guest's behalf

            $table->enum('type', ['lost', 'found'])->index();
            $table->string('name');
            $table->string('category')->index();
            $table->string('location')->index();
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('contact')->nullable();
            $table->string('image')->nullable(); // single optional photo — not everyone can photograph what they lost

            $table->enum('status', ['open', 'matched', 'claimed', 'returned', 'closed'])
                  ->default('open')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
