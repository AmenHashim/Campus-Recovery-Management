<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable activity trail (FR-F4). Rows are only ever inserted and read — never updated
 * or deleted — so admins have a tamper-evident record of who did what. The subject is a
 * nullable polymorphic link to the affected record (Item, Claim, User, …) for filtering.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // the actor
            $table->string('action')->index();       // machine key, e.g. "claim.verified"
            $table->text('description');              // human-readable summary
            $table->nullableMorphs('subject');        // subject_type + subject_id
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
