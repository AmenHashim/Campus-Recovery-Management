<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A student/staff claim on a found item — verification by an Officer is a later sprint.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->foreignId('claimant_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('token', 20)->unique(); // shown to the student, checked by the office in person
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
