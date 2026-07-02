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
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->integer('amount'); // 出金金額
            $table->tinyInteger('status')->default(1); // 1: 申請中, 2: 承認済み, 3: 不許可, etc
            $table->date('withdrawal_date')->nullable(); // 出金日付
            $table->integer('withdrawal_fee');
            $table->string('bank_name');
            $table->string('branch_name');
            $table->tinyInteger('account_type'); // 1:普通 2:当座 3:貯蓄
            $table->string('account_holder');
            $table->string('account_number');
            $table->string('comment')->nullable();
            $table->string('remark')->nullable();
            $table->string('mobile_phone', 20)->nullable(); // 携帯電話番号
            $table->string('ip_address', 45)->nullable(); // 登録時のIPアドレス
            $table->string('sms_token', 6)->nullable()->comment('SMSで送信するワンタイムコード');
            $table->dateTime('sms_sent_at')->nullable()->comment('SMS送信日時');
            $table->dateTime('sms_verified_at')->nullable()->comment('SMS認証成功日時');
            $table->integer('sms_attempts')->default(0)->comment('SMS送信・入力試行回数');
            $table->dateTime('sms_expires_at')->nullable()->comment('ワンタイムコード有効期限');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->index(['member_id', 'status']);
            $table->index(['member_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
    }
};
