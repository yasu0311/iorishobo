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
        Schema::create('review_replies', function (Blueprint $table) {
            $table->id();
            $table->string('review_reply_number', 12)->unique();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('sender_type'); // 1:販売者 2:reviewテーブルのレビューの投稿者 3:サイト管理者
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reply',500);
            $table->datetime('deleted_by_admin_at')->nullable();
            $table->datetime('deleted_by_sender_at')->nullable();
            $table->string('ip_address', 45)->nullable(); // レビュー返信時のIPアドレス（IPv4/IPv6対応）
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_replies');
    }
};
