<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $status) {
            $status->id();
            $status->foreignId('user_id')->constrained()->onDelete('cascade');
            $status->foreignId('product_id')->constrained()->onDelete('cascade');
            $status->foreignId('order_id')->constrained()->onDelete('cascade');
            $status->integer('rating')->default(5);
            $status->text('comment')->nullable();
            $status->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
