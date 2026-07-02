<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MessageReplySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存のMessageレコードを取得
        $messages = \App\Models\Message::all();
        
        if ($messages->isEmpty()) {
            $this->command->error('Messageレコードが見つかりません。先にMessageSeederを実行してください。');
            return;
        }

        // 既存のUserレコードを取得
        $users = \App\Models\User::all();
        
        if ($users->isEmpty()) {
            $this->command->error('Userレコードが見つかりません。先にUserSeederを実行してください。');
            return;
        }


        // 返信は多くなりすぎないよう、各メッセージ 0〜2 件に抑える
        foreach ($messages as $message) {
            $replyCount = rand(0, 2);
            if ($replyCount === 0) {
                continue;
            }

            \App\Models\MessageReply::factory($replyCount)->create([
                'message_id' => $message->id,
                'user_id' => function () use ($users) {
                    // 管理者からの返信でない場合は、ユーザーからの返信
                    if (rand(1, 10) <= 3) {
                        return null; // 管理者からの返信
                    }
                    return $users->random()->id;
                },
                'sender_type' => function () {
                    // ランダムに送信者種別を決定
                    // 1: 販売者 (40%), 2: メッセージ投稿者 (40%), 3: 管理者 (20%)
                    $rand = rand(1, 10);
                    if ($rand <= 4) return 1; // 販売者
                    if ($rand <= 8) return 2; // メッセージ投稿者
                    return 3; // 管理者
                }
            ]);
        }

        $this->command->info('メッセージ返信を控えめな件数で作成しました。');
    }
}
