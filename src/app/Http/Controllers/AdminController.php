<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\Flashcards;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    // Admin lấy danh sách người dùng
    public function getUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Admin: Khóa/mở khóa tài khoản người dùng
    public function toggleUserStatus($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json(['message' => 'User status updated', 'user' => $user]);
    }

    // Admin xóa tài khoản của người dùng khác
    public function deleteUser(Request $request, $id)
    {
        $admin = $request->user();

        // Chỉ cho phép admin xóa user
        if ($admin->role !== 'admin') {
            return response()->json(['message' => 'Không có quyền xóa (admin)'], 403);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->tokens()->delete(); // Xóa token trước khi xóa user
        $user->delete();

        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    // Admin: Gửi thông báo cho user
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:255',
        ]);

        Notification::create([
            'user_id' => $request->user_id,
            'message' => $request->message,
        ]);

        return response()->json(['message' => 'Notification sent successfully']);
    }

    // Admin: Lấy danh sách tất cả collection
    public function getCollections()
    {
        $collections = Collections::all();
        return response()->json($collections);
    }

    // Admin: Tạo collection mới
    public function createCollection(Request $request)
    {
        $request->validate([
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'required|boolean',
        ]);

        $collection = Collections::create([
            'collection_name' => $request->collection_name,
            'description' => $request->description,
            'privacy' => $request->privacy,
            'user_id' => auth()->id(),
        ]);

        return response()->json(['message' => 'Collection created successfully', 'collection' => $collection]);
    }

    // Admin: cập nhật collection
    public function updateCollection(Request $request, $id)
    {
        $collection = Collections::find($id);
        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        $collection->update($request->only(['collection_name', 'description', 'privacy']));

        return response()->json(['message' => 'Collection updated successfully', 'collection' => $collection]);
    }

    // Admin: xóa collection
    public function deleteCollection($id)
    {
        $collection = Collections::find($id);
        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        $collection->delete();

        return response()->json(['message' => 'Collection deleted successfully']);
    }

    // Admin: Lấy danh sách flashcard trong collection
    public function getFlashcards($collectionId)
    {
        $flashcards = Flashcards::where('collection_id', $collectionId)->get();
        return response()->json($flashcards);
    }

    // Admin: Thêm flashcard
    public function addFlashcard(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'word' => 'required|string|max:255',
            'meaning' => 'required|string|max:255',
        ]);

        $flashcard = Flashcards::create($request->all());

        return response()->json(['message' => 'Flashcard added successfully', 'flashcard' => $flashcard]);
    }

    // Admin: update flashcard
    public function updateFlashcard(Request $request, $id)
    {
        $flashcard = Flashcards::find($id);
        if (!$flashcard) {
            return response()->json(['message' => 'Flashcard not found'], 404);
        }

        $flashcard->update($request->only(['word', 'meaning']));

        return response()->json(['message' => 'Flashcard updated successfully', 'flashcard' => $flashcard]);
    }

    // Admin: xóa flashcard
    public function deleteFlashcard($id)
    {
        $flashcard = Flashcards::find($id);
        if (!$flashcard) {
            return response()->json(['message' => 'Flashcard not found'], 404);
        }

        $flashcard->delete();

        return response()->json(['message' => 'Flashcard deleted successfully']);
    }

}
