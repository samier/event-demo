<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('city_anchors', function (Blueprint $table) {
            $table->id();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('city');
            $table->string('region')->default('');
            $table->string('country');
            $table->string('country_code', 2);
            $table->string('timezone');
            $table->string('label');
            $table->timestamps();

            $table->unique(['latitude', 'longitude']);
            $table->index('city');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('city_anchors');
    }
};
