<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional map coordinates (FR-B1 refinement). The `location` text column stays the
 * source of truth — the MatchingEngine still scores on it, and plenty of reports will
 * never have a pin (someone reporting a lost item rarely knows the exact spot). The
 * pin is an *addition* to the name, not a replacement for it.
 *
 * decimal(10,7) gives ~1cm precision, far more than a campus needs, but it costs
 * nothing and avoids float rounding drift on repeated read/write.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        // Managed locations get coordinates too, so picking "Main Library" from the
        // list can drop the pin automatically instead of making the user hunt for it.
        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
