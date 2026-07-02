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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 12)->unique();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name', 20);
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('usage')->default(1); // 1:個人利用 2:学校利用 3:商用利用         
            $table->string('licence', 1000)->nullable();
            $table->integer('price');
            $table->integer('quantity')->default(1);
            $table->decimal('tax_rate', 4, 3); // 0.107(10.7%) 合計4桁，小数以下3桁
            $table->integer('tax_amount')->default(0);
            $table->integer('total_amount')->default(0);
            $table->integer('points_paid')->default(0);
            $table->integer('amount_paid')->default(0);
            $table->integer('transaction_fee');;
            $table->dateTime('ordered_at');
            $table->string('remark', 500)->nullable();
            $table->string('token')->nullable();
            $table->string('status')->default('pending');
            $table->dateTime('canceled_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->dateTime('paid_at')->nullable(); // 決済確定日時（Webhook COMPLETED 時）
            $table->string('ip_address', 45)->nullable(); // IPv6対応のため45文字
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index('status');
            $table->index('ordered_at');
            $table->index(['member_id', 'ordered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
