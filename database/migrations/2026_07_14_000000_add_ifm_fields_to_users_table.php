<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CPRMS — extends Laravel's default users table.
 *
 * NOTE: there is deliberately NO `role` column here.
 * Roles are owned by spatie/laravel-permission (roles + model_has_roles tables).
 * `user_type` is a separate University *identity* attribute (student vs staff) — it is
 * NOT a permission. Both share the same role: student_staff.
 *
 * Traces to: FR-A1 (registration fields), FR-E3 (reputation), BR-02 (guests are
 * never users), BR-07 (self-registration cannot elevate role).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // University identity — only meaningful for the student_staff role.
            // NULL for Officer / Super Admin accounts (they are staff of the system, not a report population).
            $table->enum('user_type', ['student', 'staff'])->nullable()->after('email');

            // Institutional identifier. Required for every account (also a login credential, FR-A2).
            $table->string('reg_no', 50)->unique()->after('user_type');

            $table->string('phone', 20)->nullable()->after('reg_no');

            // FR-E3 / BR-06 — maintained aggregate of reputation_logs (added in a later sprint).
            $table->unsignedInteger('reputation_points')->default(0)->after('phone');

            // Suspended users retain their data but cannot authenticate (FR-F1).
            $table->enum('status', ['active', 'suspended'])->default('active')->after('reputation_points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'reg_no', 'phone', 'reputation_points', 'status']);
        });
    }
};
