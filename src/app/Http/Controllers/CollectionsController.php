<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Collections;
use Illuminate\Support\Facades\Auth;

class CollectionsController extends Controller
{
    // Lấy danh sách collection
    public function index()
    {
        $userId = Auth::id();

        // Lấy tất cả các collections công khai (privacy = 1) và các collection riêng tư của người dùng hiện tại (privacy = 0)
        $collections = Collections::where('privacy', 1)
            ->orWhere(function($query) use ($userId) {
                $query->where('privacy', 0)
                    ->where('user_id', $userId);
            })
            ->get();

        return response()->json($collections);
    }

    // Tạo mới collection
    public function store(Request $request)
    {
        $request->validate([
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'boolean',
            'tag' => 'nullable|string',
            'star_count' => 'numeric|min:0|max:5'
        ]);

        $collection = Collections::create([
            'user_id' => Auth::id(),
            'collection_name' => $request->collection_name,
            'description' => $request->description,
            'privacy' => $request->privacy ?? 0,
            'tag' => $request->tag,
            'star_count' => $request->star_count ?? 0
        ]);

        return response()->json($collection, 201);
    }

    // Lấy chi tiết 1 collection
    public function show($id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($collection);
    }

    // Cập nhật collection
    public function update(Request $request, $id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);

        $collection->update($request->only(['collection_name', 'description', 'privacy', 'tag', 'star_count']));

        return response()->json($collection);
    }

    // Xóa collection
    public function destroy($id)
    {
        $collection = Collections::where('user_id', Auth::id())->findOrFail($id);
        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }

    // search api
    public function searchPublicCollections(Request $request)
    {
        $searchTerm = $request->input('query');

        $collections = Collections::where('privacy', 1)
            ->where(function ($query) use ($searchTerm) {
                $query->where('collection_name', 'like', "%$searchTerm%")
                    ->orWhereHas('tags', function ($query) use ($searchTerm) {
                        $query->where('name', 'like', "%$searchTerm%");
                    })
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('username', 'like', "%$searchTerm%");
                    });
            })
            ->with('user') // <== Bổ sung để trả về thông tin người dùng
            ->get();

        return response()->json($collections);
    }
}
