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
        Schema::create('neighbourhoods', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('city_id')->index();
            $table->string('title');
            $table->string('slug')->index();
            $table->string('search_title')->index();
            $table->bigInteger('logo')->nullable();
            $table->geometry('location', subtype: 'point')->nullable();
            $table->boolean('has_geo')->default(false);
            $table->boolean('is_checked')->default(false);
            $table->boolean('is_user_altered')->default(false);
            $table->integer('priority')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('neighbourhoods');
    }
};
