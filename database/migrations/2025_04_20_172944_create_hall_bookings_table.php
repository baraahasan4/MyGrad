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
        Schema::create('hall_bookings', function (Blueprint $table) {
            $table->id();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('guests_count')->nullable();
            $table->decimal('price', 10, 2);
            $table->integer('booked_duration')->nullable();
            $table->string('guestName')->nullable();
            $table->enum('status',['pending','confirmed','cancelled']);
            $table->enum('occasion_type',['Birthday','Wedding','Graduation','Baby_Shower','New_Year']);

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('decoration_id');
            $table->foreign('decoration_id')->references('id')->on('decorations')->onDelete('cascade');
            $table->foreignId('hospitality_id')->nullable()->constrained('hospitalities');

            $table->foreignId('approved_or_rejected_by')->nullable()->references('id')->on('users');
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
        Schema::dropIfExists('hall_bookings');
        // Schema::table('hall_bookings', function (Blueprint $table) {
        //     $table->dropColumn(['start_time', 'end_time']);
        // });
    }
};
