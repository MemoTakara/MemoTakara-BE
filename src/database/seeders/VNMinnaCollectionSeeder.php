<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VNMinnaCollectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tags cần thiết
        $baseTags = [
            'ja' => 'Tiếng Nhật',
            'minna' => 'Minna no Nihongo',
            'beginner' => 'Beginner',
            'intermediate' => 'Intermediate',
            'advanced' => 'Advanced'
        ];

        // Tạo các tag - `tags` table seeder
        foreach ($baseTags as $tagName => $description) {
            DB::table('tags')->updateOrInsert(
                ['name' => $tagName],
                [
                    'name' => $tagName,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );
        }

        // `collections` table seeder
        $collections = [
            [
                'collection_name' => 'Minna no Nihongo Bài 1',
                'description' => 'Bao gồm các từ vựng cơ bản về chào hỏi, giới thiệu bản thân, nghề nghiệp và quốc gia.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 2',
                'description' => 'Từ vựng liên quan đến đồ vật, đồ dùng học tập, các đại từ chỉ định và cách hỏi đồ vật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 3',
                'description' => 'Chủ đề về địa điểm, các tòa nhà, cách hỏi đường và phương hướng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 4',
                'description' => 'Từ vựng về thời gian, ngày tháng, giờ giấc và cách hỏi thời gian.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 5',
                'description' => 'Các phương tiện giao thông, cách hỏi đường và cách diễn đạt về di chuyển.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 6',
                'description' => 'Từ vựng về các hoạt động hàng ngày, động từ thể ます và cách diễn đạt về sở thích.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 7',
                'description' => 'Từ vựng về đồ ăn, thức uống, cách gọi món trong nhà hàng và quà tặng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 8',
                'description' => 'Tính từ miêu tả con người, đồ vật, trạng thái và cách khen ngợi.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 9',
                'description' => 'Từ vựng về sở thích, các thể loại âm nhạc, thể thao và cách diễn đạt sở thích cá nhân.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 10',
                'description' => 'Từ vựng về địa điểm trong thành phố, cách chỉ dẫn vị trí, phương hướng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 11',
                'description' => 'Từ vựng về số lượng, cách đếm đồ vật, đơn vị đếm trong tiếng Nhật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 12',
                'description' => 'Từ vựng về thời tiết, nhiệt độ, các trạng thái thời tiết trong năm.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 13',
                'description' => 'Từ vựng về chỉ đường, hỏi đường, các động từ chỉ hành động đi lại.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 14',
                'description' => 'Các động từ thể て, cách sử dụng thể て trong câu mệnh lệnh, yêu cầu.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 15',
                'description' => 'Từ vựng về gia đình, cách gọi các thành viên trong gia đình.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 16',
                'description' => 'Từ vựng về sở thích cá nhân, các hoạt động giải trí và du lịch.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 17',
                'description' => 'Các động từ thể ない, cách diễn đạt cấm đoán, nhắc nhở.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 18',
                'description' => 'Từ vựng về khả năng làm việc, các động từ thể ことができます.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 19',
                'description' => 'Từ vựng về sở thích, các động từ thể た và cách diễn đạt trải nghiệm.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 20',
                'description' => 'Từ vựng về cách nói lịch sự hơn trong giao tiếp hàng ngày.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 21',
                'description' => 'Cách nói về ý kiến, suy nghĩ và diễn đạt ý định trong tiếng Nhật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 22',
                'description' => 'Từ vựng về trang phục, quần áo và các loại phụ kiện.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 23',
                'description' => 'Từ vựng về chỉ đường, phương tiện giao thông, địa điểm.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 24',
                'description' => 'Cách diễn đạt thời gian, hẹn gặp, lời mời trong tiếng Nhật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 25',
                'description' => 'Từ vựng về dự định tương lai, cách diễn đạt mục tiêu.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
// Bài 26 - 30
            [
                'collection_name' => 'Minna no Nihongo Bài 26',
                'description' => 'Từ vựng về thể khả năng, các động từ thể khả năng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 27',
                'description' => 'Từ vựng về nghề nghiệp, công việc và môi trường làm việc.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 28',
                'description' => 'Từ vựng về tính từ, các từ mô tả cảm xúc, tính cách.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 29',
                'description' => 'Từ vựng về trạng thái sự vật, cách mô tả tình trạng của đồ vật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 30',
                'description' => 'Từ vựng về dọn dẹp, sắp xếp đồ đạc, công việc nhà.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
// Bài 31 - 50
            [
                'collection_name' => 'Minna no Nihongo Bài 31',
                'description' => 'Cách diễn đạt kế hoạch tương lai, dự định và kỳ vọng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 32',
                'description' => 'Từ vựng về điều kiện giả định, câu điều kiện trong tiếng Nhật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 33',
                'description' => 'Cách diễn đạt ý định, dự định và đề nghị trong hội thoại.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 34',
                'description' => 'Từ vựng về cách sử dụng các mẫu câu so sánh.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 35',
                'description' => 'Từ vựng về cách diễn đạt mong muốn, nguyện vọng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 36',
                'description' => 'Cách sử dụng các động từ thể bị động trong tiếng Nhật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 37',
                'description' => 'Từ vựng về các tình huống liên quan đến công việc.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 38',
                'description' => 'Cách sử dụng thể sai khiến trong giao tiếp hàng ngày.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 39',
                'description' => 'Từ vựng về các hành động liên quan đến cảm xúc và suy nghĩ.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 40',
                'description' => 'Từ vựng về đánh giá, nhận xét, và nhận định trong tiếng Nhật.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 41',
                'description' => 'Từ vựng về cách thể hiện lòng biết ơn, tặng quà và lễ nghĩa.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 42',
                'description' => 'Từ vựng liên quan đến việc chọn lựa, quyết định và so sánh.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 43',
                'description' => 'Cách diễn đạt hành động và trạng thái trong quá khứ và tương lai.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 44',
                'description' => 'Từ vựng về các hành động thường ngày và thói quen.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 45',
                'description' => 'Từ vựng về ước mơ, mong muốn và kỳ vọng trong cuộc sống.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 46',
                'description' => 'Cách diễn đạt lý do, nguyên nhân và hậu quả trong câu.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 47',
                'description' => 'Từ vựng về cách truyền đạt thông tin, báo cáo và lời nhắn.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 48',
                'description' => 'Từ vựng về các tình huống cuộc sống như đám cưới, chuyển nhà.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 49',
                'description' => 'Từ vựng về kính ngữ, cách nói lịch sự và trang trọng.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
            [
                'collection_name' => 'Minna no Nihongo Bài 50',
                'description' => 'Từ vựng về các mẫu câu trang trọng trong giao tiếp hàng ngày.',
                'privacy' => '1',
                'difficulty_level' => 'beginner',
                'is_featured' => true,
                'tags' => ['ja', 'minna', 'beginner'],
                'user_id' => 1,
            ],
        ];

        foreach ($collections as $collectionData) {
            $collection_id = DB::table('collections')->insertGetId([
                'collection_name' => $collectionData['collection_name'],
                'description' => $collectionData['description'],
                'privacy' => $collectionData['privacy'],
                'difficulty_level' => $collectionData['difficulty_level'],
                'language_front' => 'ja',
                'language_back' => 'vi',
                'is_featured' => $collectionData['is_featured'],
                'user_id' => $collectionData['user_id'],
                'total_cards' => 0, // Sẽ được cập nhật sau khi import flashcards
                'average_rating' => 0.00,
                'total_ratings' => 0,
                'total_duplicates' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Gắn tags cho collection
            foreach ($collectionData['tags'] as $tagName) {
                $tag_id = DB::table('tags')
                    ->where('name', $tagName)
                    ->value('id');

                if ($tag_id) {
                    DB::table('collection_tag')->updateOrInsert(
                        [
                            'collection_id' => $collection_id,
                            'tag_id' => $tag_id
                        ],
                        []
                    );
                }
            }

            $this->command->info("Created collection: {$collectionData['collection_name']} (ID: {$collection_id})");

        }

        $this->command->info('Minna no Nihongo Collections seeding completed successfully!');
    }
}
