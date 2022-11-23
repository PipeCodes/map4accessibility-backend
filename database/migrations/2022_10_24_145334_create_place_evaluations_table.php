<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('place_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->references('id')->on('places')->cascadeOnDelete();
            $table->foreignId('app_user_id')->references('id')->on('app_users')->cascadeOnDelete();
            $table->boolean('thumb_direction')->nullable();
            $table->text('comment')->nullable();
            $table->json('questions_answers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('place_evaluations');
    }
};
