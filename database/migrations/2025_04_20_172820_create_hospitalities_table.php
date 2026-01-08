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
        Schema::create('hospitalities', function (Blueprint $table) {
            $table->id();
            $table->string('ar_name');
            $table->string('en_name');
            $table->string('ar_description')->nullable();
            $table->string('en_description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->enum('type', ['Simple ', 'Luxurious', 'Royal']);
            
            $table->unsignedBigInteger('occasion_type_id');
            $table->foreign('occasion_type_id')->references('id')->on('occasion_types')->onDelete('cascade');
            
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hospitalities');
    }
};
