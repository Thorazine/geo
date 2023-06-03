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
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('country_id');
            $table->string('title');
            $table->string('title_short')->nullable();
            $table->string('search_title')->index();
            $table->string('slug');
            $table->point('location')->nullable();
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
        Schema::dropIfExists('provinces');
    }
};
