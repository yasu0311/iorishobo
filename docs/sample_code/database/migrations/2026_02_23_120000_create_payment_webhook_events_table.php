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
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->string('event_id', 255);
            $table->string('provider', 64)->comment('決済会社名（例: square, stripe）');
            $table->string('event_type', 64);
            $table->dateTime('processed_at')->useCurrent();
            $table->primary(['event_id', 'provider']);
            $table->index(['provider', 'processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
    }
};
