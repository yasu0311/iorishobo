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
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_number', 12)->unique();
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->string('shop_name', 20)->unique();
            $table->tinyInteger('shop_limited')->default(0); // 0: 販売可, 1: 販売不可
            $table->tinyInteger('shop_status')->default(1); // 1: 開店中, 2: 準備中，3:閉店済
            $table->string('shop_icon')->nullable();
            $table->text('shop_information')->nullable();
            $table->text('shop_introduction')->nullable();
            $table->text('receipt_description')->nullable();
            $table->string('url', 255)->nullable();
            $table->bigInteger('total_upload_limit')->nullable();
            $table->decimal('transaction_fee_rate', 6, 4)->nullable();
            $table->tinyInteger('consumption_tax_classification_id')->default(1);
            $table->tinyInteger('admin_reply')->default(0); // 0:不可, 1:可            
            $table->tinyInteger('sale_notification')->default(0); // 0:通知なし, 1:通知あり
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['shop_status', 'shop_limited']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
