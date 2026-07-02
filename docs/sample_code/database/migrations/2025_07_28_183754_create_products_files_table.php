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
        Schema::create('products_files', function (Blueprint $table) {
            $table->id();
            $table->string('file_number', 12)->unique();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->tinyInteger('sample')->default(0); // 0: 商品, 1: 見本
            $table->string('file_name', 100);
            $table->string('file_path');
            $table->bigInteger('file_size');
            $table->text('file_description');
            $table->text('copyright')->nullable(); // 著作権
            $table->text('macro')->nullable();     // マクロ説明
            $table->dateTime('file_updated_at');
            $table->string('ip_address', 45)->nullable(); // アップロード時のIPアドレス（IPv4/IPv6対応）
            $table->tinyInteger('security_check')->default(0); // 0:未, 1:済
            $table->integer('display_order')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products_files');
    }
};
