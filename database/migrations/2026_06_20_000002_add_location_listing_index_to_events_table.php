<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Location filters constrain latitude/longitude first. This composite index
     * lets MySQL satisfy the bounding-box predicate without scanning every
     * upcoming row by date.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['latitude', 'longitude', 'status', 'created_time'], 'events_location_listing_index');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex('events_location_listing_index');
        });
    }
};
