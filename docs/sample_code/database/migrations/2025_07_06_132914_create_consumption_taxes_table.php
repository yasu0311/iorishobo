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
        Schema::create('consumption_taxes', function (Blueprint $table) {
            $table->id(); // 任意のユニークID
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('tax_rate', 6, 4); // 例：0.10（10%）、0.08（8%）
            $table->tinyInteger('classification_id'); // 消費税区分ID（例：1:非課税, 2:課税）
            $table->string('classification', 20); // 例：「課税(10%)」
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->index('classification_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumption_taxes');
    }
};
