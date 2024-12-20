<?php

use App\Helper\PlaceEvaluationStatus;
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
            $table->string('status')->default(PlaceEvaluationStatus::Pending->value);
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
            $table->dropColumn('status');
        });
    }
};
