<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consumption_taxes', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('tax_rate', 6, 4); // 例: 0.1000 = 10%
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        $now = now();

        DB::table('consumption_taxes')->insert([
            [
                'start_date' => '2014-04-01',
                'end_date' => '2019-09-30',
                'tax_rate' => 0.08,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'start_date' => '2019-10-01',
                'end_date' => '2200-01-01',
                'tax_rate' => 0.10,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('consumption_taxes');
    }
};
