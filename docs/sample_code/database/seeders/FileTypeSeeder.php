<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FileType;

class FileTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fileTypes = [
            ['id' => 1, 'file_type_name' => 'word', 'icon' => 'image/file_type/word.png'],
            ['id' => 2, 'file_type_name' => 'excel', 'icon' => 'image/file_typeexcel.png'],
            ['id' => 3, 'file_type_name' => 'pdf', 'icon' => 'image/file_typepdf.png'],
            ['id' => 4, 'file_type_name' => '一太郎', 'icon' => 'image/file_type/ichitaro.png'],
            ['id' => 5, 'file_type_name' => 'その他文書', 'icon' => 'image/file_type/other_doc.png'],
            ['id' => 6, 'file_type_name' => '画像', 'icon' => 'image/file_type/image.png'],
            ['id' => 7, 'file_type_name' => '音声', 'icon' => 'image/file_type/audio.png'],
            ['id' => 8, 'file_type_name' => '動画', 'icon' => 'image/file_type/movie.png'],
        ];
    
        foreach ($fileTypes as $data) {
            FileType::create([
                'id' => $data['id'],
                'file_type_name' => $data['file_type_name'],
                'icon' => $data['icon'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
