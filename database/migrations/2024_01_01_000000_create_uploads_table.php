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
            $table->string('file_hash', 32)->nullable(); // MD5 hash for deduplication
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_token')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index(['user_id']);
            $table->index(['guest_token']);
            $table->index(['file_hash', 'size']); // For deduplication
            $table->index(['type']); // For filtering by file type
        });
    }

    public function down()
    {
        Schema::dropIfExists('uploads');
    }
};
