<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['id' => 1, 'subject' => '国語', 'display_order' => 1],
            ['id' => 2, 'subject' => '数学・算数', 'display_order' => 2],
            ['id' => 3, 'subject' => '英語', 'display_order' => 3],
            ['id' => 4, 'subject' => '理科', 'display_order' => 4],
            ['id' => 5, 'subject' => '社会', 'display_order' => 5],
            ['id' => 6, 'subject' => 'その他', 'display_order' => 6],
        ];
    
        \App\Models\Subject::insert($subjects);
    }
}
