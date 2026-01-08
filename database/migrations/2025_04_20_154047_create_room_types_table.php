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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->enum('type_name_en',['Single','Double','Triple','Executive Suite','Disabled Room','Smoking/Non-Smoking Room']);
            $table->enum('type_name_ar',['فردية','مزدوجة','ثلاثية','جناح تنفيذي','غرفة لذوي الاحتياجات الخاصة','غرفة تدخين/غير تدخين']);
            $table->text('description_en');
            $table->text('description_ar');
            $table->decimal('price', 10, 2);
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
        Schema::dropIfExists('room_types');
    }
};
