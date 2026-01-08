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
        Schema::create('restaurant_orders', function (Blueprint $table) {
            $table->id();
            $table->integer('table_number')->nullable();
            $table->integer('room_number')->nullable();
            $table->integer('number_of_people')->nullable();
            $table->dateTime('preferred_time');
            $table->decimal('table_price', 10, 2);
            $table->enum('order_type',['room','table']);
            $table->enum('status',['pending','preparing','cancelled']);
            $table->dateTime('reservation_end_time')->nullable();
            $table->integer('booked_duration')->nullable();
            $table->decimal('total_price', 10, 2);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->foreignId('approved_or_rejected_by')->nullable()->references('id')->on('users');
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
        Schema::dropIfExists('restaurant_orders');
        // Schema::table('restaurant_orders', function (Blueprint $table) {
        //     $table->dropColumn('reservation_end_time');
        // });
    }
};
