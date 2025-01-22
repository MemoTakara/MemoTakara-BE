<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FlashcardsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('flashcards')->insert([
            [
                'front' => '菜', 
                'back' => 'rau quả', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=%E8%8F%9C&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '茶', 
                'back' => 'Trà', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=茶&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '米饭', 
                'back' => 'cơm', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=米饭&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '苹果', 
                'back' => 'quả táo', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=苹果&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '东西', 
                'back' => 'đồ vật', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=东西&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '块', 
                'back' => 'cái (lượng từ)', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=块&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '买', 
                'back' => 'mua', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=买&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '钱', 
                'back' => 'tiền bạc', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=钱&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '商店', 
                'back' => 'cửa hàng', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=商店&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '不客气', 
                'back' => 'Không có gì', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=不客气&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '对不起', 
                'back' => 'Xin lỗi', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=对不起&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '没关系', 
                'back' => 'không sao đâu', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=没关系&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '请', 
                'back' => 'Xin vui lòng, mời', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=请&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '谢谢', 
                'back' => 'Cảm ơn', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=谢谢&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '再见', 
                'back' => 'tạm biệt', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=再见&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '分钟', 
                'back' => 'phút', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=分钟&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '年', 
                'back' => 'Năm', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=年&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '时候', 
                'back' => 'khi', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=时候&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '现在', 
                'back' => 'Hiện nay', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=现在&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '飞机', 
                'back' => 'máy bay', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=飞机&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '火车站', 
                'back' => 'Ga xe lửa', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=火车站&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '前边', 
                'back' => 'đằng trước', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=前边&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '后边', 
                'back' => 'phía sau', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=后边&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '星期', 
                'back' => 'Tuần', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=星期&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '高兴', 
                'back' => 'Vui mừng', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=高兴&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '电脑', 
                'back' => 'máy tính', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=电脑&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '电视', 
                'back' => 'tivi', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=电视&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '电影', 
                'back' => 'Bộ phim', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=电影&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '衣服', 
                'back' => 'quần áo', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=衣服&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '桌子', 
                'back' => 'bàn', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=桌子&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '椅子', 
                'back' => 'Ghế', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=椅子&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '零', 
                'back' => 'số 0', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=零&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '妈妈', 
                'back' => 'Mẹ', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=妈妈&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '儿子', 
                'back' => 'con trai', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=儿子&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
            [
                'front' => '女儿', 
                'back' => 'con gái', 
                'audio_file' => 'https://translate.google.com/translate_tts?ie=UTF-8&q=女儿&tl=zh-CN&client=tw-ob',
                'status' => 'new',
                'collection_id' => 1, // HSK 1
            ],
        ]);
    }
}
