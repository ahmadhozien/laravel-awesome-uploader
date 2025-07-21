<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('url');
            $table->string('type');
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_token')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('uploads');
    }
};
