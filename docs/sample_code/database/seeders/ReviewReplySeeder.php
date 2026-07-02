<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\User;
use App\Models\ReviewReply;

class ReviewReplySeeder extends Seeder
{
    public function run(): void
    {
        $adminUsers = User::where('role', 1)->get();
        $memberUsers = User::where('role', 0)->get();

        Review::all()->each(function ($review) use ($adminUsers, $memberUsers) {

            // 返信は控えめにしてレビュー画面が過密にならないようにする
            $replyCount = rand(0, 2);

            ReviewReply::factory($replyCount)->make()->each(function ($reply) use ($review, $adminUsers, $memberUsers) {

                $reply->review_id = $review->id;

                switch ($reply->sender_type) {
                    case 1: // 販売者（会員からランダム）
                        $reply->user_id = $memberUsers->random()->id;
                        break;

                    case 2: // レビュー投稿者
                        $reply->user_id = $review->order->product->shop->member->user->id;
                        break;

                    case 3: // 管理者
                        $reply->user_id = $adminUsers->random()->id;
                        break;
                }

                $reply->save();
            });
        });
    }
}