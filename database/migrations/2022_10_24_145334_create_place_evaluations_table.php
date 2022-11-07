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
            $table->bigInteger('app_user_id')->unsigned();
            $table->unsignedBigInteger('google_place_id')->nullable();
            $table->string('name')->nullable();
            $table->string('place_type')->nullable();
            $table->string('country');
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 11, 8);
            $table->boolean('thumb_direction')->nullable();
            $table->text('comment')->nullable();
            $table->json('questions_answers')->nullable();
            $table->timestamps();

            $table->foreign('app_user_id')->references('id')->on('app_users');

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
