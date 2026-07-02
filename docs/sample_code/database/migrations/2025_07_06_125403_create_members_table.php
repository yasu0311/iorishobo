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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_number', 12)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nickname', 15)->unique();
            $table->tinyInteger('company')->default(0); // 0: 個人, 1: 法人
            $table->string('last_name', 50);
            $table->string('first_name', 50);
            $table->string('last_name_kana', 50);
            $table->string('first_name_kana', 50);
            $table->string('postal_code', 8);
            $table->string('address_prefecture', 10);
            $table->string('address_city', 255);
            $table->string('address_block', 255);
            $table->string('address_building', 255)->nullable();
            $table->string('phone_number', 16);
            $table->string('company_name', 50)->nullable();
            $table->string('company_name_kana', 100)->nullable();
            $table->string('company_postal_code', 8)->nullable();
            $table->string('company_prefecture', 10)->nullable();
            $table->string('company_city', 255)->nullable();
            $table->string('company_block', 255)->nullable();
            $table->string('company_building', 255)->nullable();
            $table->string('company_phone_number', 16)->nullable();
            $table->string('member_icon', 255)->nullable();
            $table->tinyInteger('message_notification')->default(1);
            $table->tinyInteger('sale_notification')->default(1);
            $table->integer('balance')->nullable(); //初期は使わない。通帳表示が重くなってから
            $table->string('ip_address', 45)->nullable()->comment('登録時のIPアドレス');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
