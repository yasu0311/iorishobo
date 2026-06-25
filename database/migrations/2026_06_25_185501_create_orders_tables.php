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
            $table->unsignedBigInteger('colorme_sales_id')->nullable()->unique();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('order_number', 50)->unique();
            $table->timestamp('ordered_at');
            $table->string('device', 50)->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('tax_amount');
            $table->unsignedInteger('shipping_fee');
            $table->unsignedInteger('payment_fee')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->string('discount_name')->nullable();
            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons')
                ->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();
            $table->unsignedInteger('point_discount')->default(0);
            $table->unsignedInteger('external_point_discount')->default(0);
            $table->unsignedInteger('total');
            $table->string('payment_method', 30);
            $table->string('payment_status', 30);
            $table->string('shipping_status', 30);
            $table->timestamp('shipped_at')->nullable();
            $table->string('tracking_number', 50)->nullable();
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->constrained('shipping_methods')
                ->nullOnDelete();
            $table->string('shipping_method_name')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('shipping_note')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->unsignedInteger('refund_amount')->default(0);
            $table->timestamp('refunded_at')->nullable();

            // buyer_*
            $table->string('buyer_name', 100);
            $table->string('buyer_email');
            $table->string('buyer_phone', 20)->nullable();
            $table->string('buyer_mobile', 20)->nullable();
            $table->char('buyer_postal_code', 7);
            $table->string('buyer_prefecture', 20);
            $table->string('buyer_address_line1');
            $table->string('buyer_address_line2')->nullable();

            // shipping_*
            $table->string('shipping_name', 100);
            $table->string('shipping_name_kana', 100)->nullable();
            $table->string('shipping_phone', 20);
            $table->char('shipping_postal_code', 7);
            $table->string('shipping_prefecture', 20);
            $table->string('shipping_address_line1');
            $table->string('shipping_address_line2')->nullable();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('colorme_sales_detail_id')->nullable()->unique();
            $table->foreignId('product_variant_id')
                ->nullable()
                ->constrained('product_variants')
                ->nullOnDelete();
            $table->string('product_name');
            $table->string('variant_label')->nullable();
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->text('reason')->nullable();
            $table->string('stripe_refund_id')->nullable();
            $table->foreignId('recorded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('watchlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')
                ->nullable()
                ->constrained('customers')
                ->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('reason');
            $table->boolean('is_active')->default(true);
            $table->foreignId('source_order_id')
                ->nullable()
                ->constrained('orders')
                ->nullOnDelete();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->foreignId('deactivated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watchlist_entries');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
