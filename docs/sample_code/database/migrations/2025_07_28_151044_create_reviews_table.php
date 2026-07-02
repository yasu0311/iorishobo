<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_number', 12)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->integer('rating')->default(5); // 1:不満 2:やや不満 3:普通 4:やや満足 5:満足         
            $table->string('review',500)->nullable();
            $table->datetime('deleted_by_sender_at')->nullable();
            $table->datetime('deleted_by_admin_at')->nullable();
            $table->string('ip_address', 45)->nullable(); // レビュー投稿時のIPアドレス（IPv4/IPv6対応）
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
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
