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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('message_number', 12)->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('public_sender')->default(1);
            $table->integer('public_shop')->default(1);
            $table->string('title', 20);
            $table->text('message');
            $table->datetime('deleted_by_sender_at')->nullable();
            $table->datetime('deleted_by_admin_at')->nullable();
            $table->string('ip_address', 45)->nullable(); // メッセージ投稿時のIPアドレス（IPv4/IPv6対応）
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['product_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
