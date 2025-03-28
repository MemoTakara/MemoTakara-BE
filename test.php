public function searchPublicCollections(Request $request)
{
$searchTerm = $request->input('query');

// Lấy danh sách username của admin
$adminUsernames = User::where('role', 'admin')->pluck('username')->toArray();

// Nếu tìm kiếm "MemoTakara", chỉ trả về collection của admin
if ($searchTerm === "MemoTakara") {
$collections = Collections::whereHas('user', function ($query) {
$query->where('role', 'admin');
})->get();
} else {
// Tìm kiếm bình thường nhưng không tìm theo username nếu là admin
$collections = Collections::where('privacy', 1)
->where(function ($query) use ($searchTerm, $adminUsernames) {
$query->where('collection_name', 'like', "%$searchTerm%")
->orWhereHas('tags', function ($query) use ($searchTerm) {
$query->where('name', 'like', "%$searchTerm%");
})
->orWhereHas('user', function ($query) use ($searchTerm, $adminUsernames) {
// Loại bỏ tìm kiếm theo username của admin
$query->where('username', 'like', "%$searchTerm%")
->whereNotIn('username', $adminUsernames);
});
})
->get();
}

return response()->json($collections);
}
