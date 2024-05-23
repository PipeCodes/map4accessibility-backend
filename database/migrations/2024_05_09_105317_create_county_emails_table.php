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
        Schema::create('county_emails', function (Blueprint $table) {
            $table->id();
            $table->string('county_iso')->unique();
            $table->string('email');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('county_emails');
    }
};
