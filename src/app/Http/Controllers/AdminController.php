<?php

namespace App\Http\Controllers;

use App\Models\Collections;
use App\Models\Flashcards;
use App\Models\FlashcardStatus;
use App\Models\Notification;
use App\Models\Tags;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    // Admin lấy danh sách người dùng
    public function getUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    // Admin tạo user mới
    public function addUsers(Request $request)
    {
        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'username' => 'required|string|max:255|unique:users,username',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'nullable|string|in:user,admin', // Cho phép role là null
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Tạo người dùng mới
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'user', // Nếu không xác định, gán giá trị 'user' mặc định
            'is_active' => true, // Gán mặc định là true
        ]);

        // Ẩn mật khẩu khỏi thông tin người dùng trả về
        $user->makeHidden(['password']);

        return response()->json(['message' => 'User created successfully', 'user' => $user], 201);
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

    // Admin: get all notification
    public function getNotifications(Request $request)
    {
        // Nếu cần kiểm tra quyền admin, bạn có thể thêm logic ở đây
        $notifications = Notification::where('user_id', Auth::id())->get();
        return response()->json($notifications);
    }

    // Admin: Gửi thông báo cho user
    public function sendNotification(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string',
            'type' => 'required|string',
            'sender_id' => 'nullable|exists:users,id',
            'data' => 'nullable|json',
        ]);

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'sender_id' => $request->sender_id,
            'message' => $request->message,
            'type' => $request->type,
            'data' => $request->data,
            'is_read' => false,
        ]);

        return response()->json($notification, 201);
    }

    // Admin: Lấy danh sách tất cả collection
    public function getAllCollections()
    {
        // Lấy tất cả collections trong hệ thống
        $collections = Collections::with([
            'user:id,username', // Chỉ lấy id và username của user
            'tags', // Lấy danh sách tags
//            'ratings', // Lấy danh sách đánh giá
//            'ratings:id,collection_id,rating',
//            'ratings.user:id,username, rating', // Lấy thông tin người đánh giá (chỉ lấy id, name)
        ])->get();

        // Tính số sao trung bình cho mỗi collection
        $collections->each(function ($collection) {
            $collection->average_rating = optional($collection->ratings)->avg('rating') ?? 0; // Mặc định 0 nếu chưa có đánh giá
        });

        return response()->json($collections);
    }

    // Admin: Tạo collection mới
    public function createCollection(Request $request)
    {
        $request->validate([
            'collection_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'privacy' => 'required|boolean',
            'tags' => 'nullable|array', // Kiểm tra nếu tags là mảng
            'tags.*' => 'string|distinct', // Mỗi tag là chuỗi và không trùng lặp
        ]);

        // Tạo collection
        $collection = Collections::create([
            'collection_name' => $request->collection_name,
            'description' => $request->description,
            'privacy' => $request->privacy,
            'user_id' => auth()->id(), // Lấy ID của user hiện tại
        ]);

        // Nếu có tags, lưu vào bảng tags và bảng collection_tag
        $request->validate([
            'tags' => 'nullable',
        ]);

        $tags = $request->tags;
        if (is_string($tags)) {
            $tags = explode(',', $tags);
        }

        $tagIds = [];
        if (is_array($tags)) {
            foreach ($tags as $tagName) {
                $tagName = trim($tagName);
                if ($tagName === '') continue;

                $tag = Tags::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
        }

// Gán tags vào collection
        $collection->tags()->sync($tagIds);

        return response()->json(['message' => 'Collection created successfully', 'collection' => $collection]);
    }


    // Admin: cập nhật collection
    public function updateCollection(Request $request, $id)
    {
        $collection = Collections::find($id);
        if (!$collection) {
            return response()->json(['message' => 'Collection not found'], 404);
        }

        // Cập nhật collection
        $collection->update($request->only(['collection_name', 'description', 'privacy']));

        // Cập nhật tags nếu có
        if ($request->has('tags')) {
            $request->validate([
                'tags' => 'nullable',
            ]);

            $tags = $request->tags;
            if (is_string($tags)) {
                $tags = explode(',', $tags);
            }

            $tagIds = [];
            if (is_array($tags)) {
                foreach ($tags as $tagName) {
                    $tagName = trim($tagName);
                    if ($tagName === '') continue;

                    $tag = Tags::firstOrCreate(['name' => $tagName]);
                    $tagIds[] = $tag->id;
                }
            }

// Gán tags vào collection
            $collection->tags()->sync($tagIds);
        }

        return response()->json([
            'message' => 'Collection updated successfully',
            'collection' => $collection->load('tags') // Trả về luôn danh sách tags mới
        ]);
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

    /**
     * Lấy danh sách tất cả flashcards (chỉ dành cho admin)
     */
    public function getAllFlashcards(Request $request)
    {
        // Kiểm tra quyền admin
        if (!Auth::user() || Auth::user()->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        // Lấy tất cả flashcards, bao gồm collection_id và user_id của collection
        $flashcards = Flashcards::with([
            'collection:id,collection_name,user_id', // Lấy thông tin collection (chỉ lấy ID, tên collection, user_id)
            'collection.user:id,username,email', // Lấy thông tin user (chỉ lấy ID, tên, email)
            'statuses' => function ($query) {
                $query->with('user:id,username')->latest();
            }
        ])->get();

        return response()->json($flashcards);
    }

    // Admin: Lấy danh sách flashcard trong collection
    public function getFlashcards($collectionId)
    {
        $flashcards = Flashcards::where('collection_id', $collectionId)
            ->with([
                'collection:id,collection_name,user_id',
                'collection.user:id,username,email',
                'statuses' => function ($query) {
                    $query->with('user:id,username')->latest();
                }
            ])
            ->get();
        return response()->json($flashcards);
    }

    // Admin: Thêm flashcard
    public function addFlashcard(Request $request)
    {
        $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'front' => 'required|string|max:255',
            'back' => 'required|string|max:255',
            'pronunciation' => 'nullable|string|max:255',
            'kanji' => 'nullable|string|max:255',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg,flac',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:new,learning,re-learning,young,mastered',
        ]);

        $data = $request->only([
            'collection_id', 'front', 'back', 'pronunciation', 'kanji'
        ]);

        if ($request->hasFile('audio_file')) {
            $data['audio_file'] = $request->file('audio_file')->store('audio', 'public');
        }

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('images', 'public');
        }

        $flashcard = Flashcards::create($data);

        // Nếu có status được chọn, thêm vào flashcard_statuses
        $status = $request->input('status', 'new'); // Mặc định là 'new'

        FlashcardStatus::create([
            'user_id' => Auth::id(), // Hoặc admin ID hoặc 1 user mặc định nếu cần
            'flashcard_id' => $flashcard->id,
            'status' => $status,
            'interval' => 0,
            'ease_factor' => 2.5,
            'repetitions' => 0,
            'last_reviewed_at' => null,
            'next_review_at' => null,
        ]);

        return response()->json([
            'message' => 'Flashcard added successfully',
            'flashcard' => $flashcard
        ]);
    }

    // Admin: update flashcard
    public function updateFlashcard(Request $request, $id)
    {
        $flashcard = Flashcards::find($id);
        if (!$flashcard) {
            return response()->json(['message' => 'Flashcard not found'], 404);
        }

        // Validate input
        $validated = $request->validate([
            'collection_id' => 'required|exists:collections,id',
            'front' => 'required|string|max:255',
            'back' => 'required|string|max:255',
            'pronunciation' => 'nullable|string|max:255',
            'kanji' => 'nullable|string|max:255',
            'audio_file' => 'nullable|file|mimes:mp3,wav,m4a,ogg,flac',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:new,learning,re-learning,young,mastered',
        ]);

        // Cập nhật các trường cơ bản
        $flashcard->collection_id = $validated['collection_id'];
        $flashcard->front = $validated['front'];
        $flashcard->back = $validated['back'];
        $flashcard->pronunciation = $validated['pronunciation'] ?? null;
        $flashcard->kanji = $validated['kanji'] ?? null;

        // Nếu có file âm thanh mới thì cập nhật
        if ($request->hasFile('audio_file')) {
            // Xóa file âm thanh cũ nếu có
            if ($flashcard->audio_file) {
                Storage::disk('public')->delete($flashcard->audio_file);
            }

            $audioPath = $request->file('audio_file')->store('audio_files', 'public');
            $flashcard->audio_file = $audioPath;
        }

        // Nếu có hình ảnh mới thì cập nhật
        if ($request->hasFile('image')) {
            // Xóa hình cũ nếu có
            if ($flashcard->image) {
                Storage::disk('public')->delete($flashcard->image);
            }

            $imagePath = $request->file('image')->store('images', 'public');
            $flashcard->image = $imagePath;
        }

        $flashcard->save();

        // Cập nhật trạng thái nếu có
        if ($request->has('status')) {
            $statusRecord = FlashcardStatus::where('user_id', Auth::id())
                ->where('flashcard_id', $flashcard->id)
                ->first();

            if ($statusRecord) {
                $statusRecord->update(['status' => $request->status]);
            } else {
                FlashcardStatus::create([
                    'user_id' => Auth::id(),
                    'flashcard_id' => $flashcard->id,
                    'status' => $request->status,
                    'interval' => 0,
                    'ease_factor' => 2.5,
                    'repetitions' => 0,
                ]);
            }
        }

        $flashcard->refresh();

        return response()->json([
            'message' => 'Flashcard updated successfully',
            'flashcard' => $flashcard
        ]);
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
