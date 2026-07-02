<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            ['id' => 1, 'grade' => '大学以上', 'display_order' => 1],
            ['id' => 2, 'grade' => '高校', 'display_order' => 2],
            ['id' => 3, 'grade' => '中学', 'display_order' => 3],
            ['id' => 4, 'grade' => '小学校', 'display_order' => 4],
            ['id' => 5, 'grade' => '未就学', 'display_order' => 5],
            ['id' => 6, 'grade' => 'その他', 'display_order' => 6],
        ];
    
        \App\Models\Grade::insert($grades);
    }
}
