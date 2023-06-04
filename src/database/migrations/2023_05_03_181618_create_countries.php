<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('title')->index();
            $table->string('slug')->index();
            $table->bigInteger('logo')->nullable();
            $table->point('location')->nullable();
            $table->string('geo_province')->default('administrative_area_level_2,administrative_area_level_1');
            $table->boolean('has_geo')->default(false);
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_user_altered')->default(false);
            $table->integer('priority')->default(0);

            $table->timestampsTz();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
