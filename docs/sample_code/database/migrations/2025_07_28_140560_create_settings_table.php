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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('type')->default(1)->comment('1:全体設定, 2:個別メンバー設定');
            $table->foreignId('shop_id')->nullable()->constrained('shops')->cascadeOnDelete();
            $table->boolean('can_override')->nullable()->comment('個別設定が可能か');          
            $table->string('setting_key');
            $table->integer('value_type')->default(0)->comment('1:INT 2:DECIMAL 3:VARCHAR 4:TINYINT 5:BIGINT'); 
            $table->string('description')->nullable();
            $table->integer('value_int')->nullable();
            $table->decimal('value_decimal', 15, 4)->nullable();
            $table->string('value_string')->nullable();
            $table->tinyInteger('value_tinyint')->nullable();
            $table->tinyInteger('value_boolean')->nullable();
            $table->bigInteger('value_bigint')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['setting_key', 'shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
