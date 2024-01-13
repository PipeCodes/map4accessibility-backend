<?php

use App\Helper\QuestionType;
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
        Schema::table('rate_questions', function (Blueprint $table) {
            $table->enum('question_type', QuestionType::values());
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rate_questions', function (Blueprint $table) {
            $table->dropColumn('question_type');
        });
    }
};
