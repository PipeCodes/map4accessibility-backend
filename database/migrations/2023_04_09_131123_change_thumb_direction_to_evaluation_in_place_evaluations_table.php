<?php

use App\Helper\Evaluation;
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
        Schema::table('place_evaluations', function (Blueprint $table) {
            $table->dropColumn('thumb_direction');
            $table->tinyInteger('evaluation')
                ->default(Evaluation::Neutral->value);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('place_evaluations', function (Blueprint $table) {
            $table->dropColumn('evaluation');
            $table->boolean('thumb_direction')->nullable();
        });
    }
};
