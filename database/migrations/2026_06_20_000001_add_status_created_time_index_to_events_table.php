<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The feed filters by status + created_time on every request and paginate()
     * runs a COUNT(*) with the same predicates. A composite index keeps both the
     * listing query and total count fast on the 1.25M-row dataset.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->index(['status', 'created_time']);
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_time']);
        });
    }
};
