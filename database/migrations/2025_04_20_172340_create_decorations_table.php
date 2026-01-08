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
        Schema::create('decorations', function (Blueprint $table) {
            $table->id();
            $table->string('ar_decor_name');
            $table->string('en_decor_name');
            $table->string('image');
            $table->decimal('price', 10, 2);

            $table->unsignedBigInteger('occasion_type_id');
            $table->foreign('occasion_type_id')->references('id')->on('occasion_types')->onDelete('cascade');
         //$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('decorations');
    }
};
