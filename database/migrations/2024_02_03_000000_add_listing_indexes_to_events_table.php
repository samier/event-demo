<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The visual listings filter by date (created_time) and by location
     * (latitude/longitude bounding box). On the full 1.25M-row dataset those
     * predicates would otherwise force a full table scan, so add covering indexes.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index('created_time');
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['created_time']);
            $table->dropIndex(['latitude', 'longitude']);
        });
    }
};
