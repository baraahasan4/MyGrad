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
        Schema::create('restaurant_order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity');
            $table->decimal('total_price', 10, 2);

            $table->unsignedBigInteger('restaurant_order_id');
            $table->foreign('restaurant_order_id')->references('id')->on('restaurant_orders')->onDelete('cascade');

            $table->unsignedBigInteger('menu_item_id');
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');
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
        Schema::dropIfExists('restaurant_order_items');
    }
};
