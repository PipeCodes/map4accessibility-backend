<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rate_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_question_id')->references('id')->on('rate_questions')->cascadeOnDelete();
            $table->string('body');
            $table->string('slug');
            $table->integer('order')->default(1);
            $table->unique(['rate_question_id', 'slug']);
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
        Schema::dropIfExists('rate_answers');
    }
};
