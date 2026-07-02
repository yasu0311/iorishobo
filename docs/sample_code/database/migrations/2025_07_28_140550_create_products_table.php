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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_number', 12)->unique();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->tinyInteger('product_limited')->default(0); // 0:販売可, 1:販売不可
            $table->tinyInteger('product_status')->default(1); // 0:準備中, 1:販売中, 2:終了
            $table->string('product_name', 20);
            $table->string('product_image', 255)->nullable();
            $table->string('product_summary', 40)->nullable();
            $table->text('product_description');
            $table->text('update_information')->nullable();
            $table->integer('price_for_personal')->nullable();
            $table->integer('price_for_commercial')->nullable();
            $table->integer('price_for_school')->nullable();
            $table->integer('display_order')->nullable();
            $table->integer('ranking')->nullable();
            $table->integer('total_sales')->nullable();
            $table->decimal('rating_average', 2, 1)->nullable();            
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['product_status', 'product_limited']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
