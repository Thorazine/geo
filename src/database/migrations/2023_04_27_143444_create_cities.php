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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('province_id')->nullable();
            $table->bigInteger('country_id')->nullable();
            $table->string('title');
            $table->string('search_title')->index();
            $table->string('slug');
            $table->text('description')->nullable();
            $table->point('location')->nullable();
            $table->integer('population')->nullable();
            $table->boolean('has_geo')->default(false);
            $table->boolean('is_user_altered')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
