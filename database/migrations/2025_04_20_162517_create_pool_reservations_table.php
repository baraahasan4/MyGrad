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
        Schema::create('pool_reservations', function (Blueprint $table) {
            $table->id();
            $table->decimal('price_for_person', 10, 2);
            $table->integer('number_of_people');
            $table->decimal('total_price', 10, 2);
            $table->date('date');
            $table->enum('time',['morning','evening']);
            $table->enum('status',['pending','confirmed','cancelled']);
            $table->string('guest_name')->nullable();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreignId('approved_by')->nullable()->references('id')->on('users');

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
        Schema::dropIfExists('pool_reservations');
    }
};
