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
        Schema::create('massage_requests', function (Blueprint $table) {
            $table->id();
            $table->dateTime('preferred_time');
            $table->decimal('price', 10, 2);
            $table->enum('gender',['male','female']);
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
        Schema::dropIfExists('massage_requests');
    }
};
