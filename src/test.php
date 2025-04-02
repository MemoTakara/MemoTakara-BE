namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JLPTVocabCollectionSeeder extends Seeder
{
/**
* Run the database seeds.
*/
public function run(): void
{
// Chèn dữ liệu vào bảng tags
$tags = [
'Japanese',
];

foreach ($tags as $tag) {
DB::table('tags')->insert([
'name' => $tag,
]);
}

// Chèn dữ liệu vào bảng collections
$collections = [
[
'collection_name' => 'JLPT N5 Vocabulary Collection',
'description' => 'Includes basic words (about 800) for beginners, covering everyday topics like greetings, numbers, time, and simple verbs.',
'privacy' => '1',
'tag' => 'Japanese',
'user_id' => 1, // ID của admin
],
[
'collection_name' => 'JLPT N4 Vocabulary Collection',
'description' => 'Expands to around 1,500 words, adding more verbs, adjectives, and expressions for daily conversations and simple reading comprehension.',
'privacy' => '1',
'tag' => 'Japanese',
'user_id' => 1, // ID của admin
],
[
'collection_name' => 'JLPT N3 Vocabulary Collection',
'description' => 'Contains about 3,750 words, covering more abstract terms and nuanced expressions for intermediate learners, enabling smoother conversations and reading.',
'privacy' => '1',
'tag' => 'Japanese',
'user_id' => 1, // ID của admin
],
[
'collection_name' => 'JLPT N2 Vocabulary Collection',
'description' => 'Features around 6,000 words, including formal and business-related vocabulary, helping learners understand news, essays, and professional discussions.',
'privacy' => '1',
'tag' => 'Japanese',
'user_id' => 1, // ID của admin
],
[
'collection_name' => 'JLPT N1 Vocabulary Collection',
'description' => 'The most advanced level, with about 10,000 words, covering complex and literary expressions for near-native comprehension of academic, business, and literary texts.',
'privacy' => '1',
'tag' => 'Japanese',
'user_id' => 1, // ID của admin
],
];

foreach ($collections as $collection) {
$collectionId = DB::table('collections')->insertGetId([
'collection_name' => $collection['collection_name'],
'description' => $collection['description'],
'privacy' => $collection['privacy'],
'tag' => $collection['tag'],
'user_id' => $collection['user_id'],
]);

// Lấy id của tag vừa được chèn
$tagId = DB::table('tags')->where('name', $collection['tag'])->first()->id;

// Chèn dữ liệu vào bảng collection_tag
DB::table('collection_tag')->insert([
'collection_id' => $collectionId,
'tag_id' => $tagId,
]);
}
}
}
