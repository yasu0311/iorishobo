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
        Schema::create('message_replies', function (Blueprint $table) {
            $table->id();
            $table->string('message_reply_number', 12)->unique();
            $table->foreignId('message_id')->constrained()->onDelete('cascade');
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        $table->tinyInteger('sender_type'); // 1:販売者 2:メッセージ投稿者 3:サイト管理者
        $table->string('reply',1000); // 回答本文 
        $table->datetime('deleted_by_admin_at')->nullable();
        $table->datetime('deleted_by_sender_at')->nullable();
        $table->string('ip_address', 45)->nullable(); // メッセージ返信時のIPアドレス（IPv4/IPv6対応）
        $table->dateTime('created_at')->nullable();
        $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_replies');
    }
};
