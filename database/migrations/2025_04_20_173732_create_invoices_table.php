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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->dateTime('date');
            $table->decimal('price', 10, 2);
            $table->enum('status',['unpaid','paid','cancelled']);
            $table->enum('item_type', ['room_booking', 'massage', 'pool', 'restaurant','hall_bookings']);
            $table->unsignedBigInteger('item_id');
            $table->string('card_last4')->nullable();
            $table->string('card_brand')->nullable();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('invoices');
    }
};
