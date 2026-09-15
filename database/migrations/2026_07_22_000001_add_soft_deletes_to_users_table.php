<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FR-F1 — account removal is a soft delete. The row (and every item, claim and
 * audit log that points at it) stays in the database; the account simply
 * disappears from the app and can no longer authenticate. Only an admin can do
 * it — users cannot delete themselves (BR-07: identity is office-managed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
