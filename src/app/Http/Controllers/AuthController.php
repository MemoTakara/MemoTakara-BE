<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    // Đăng ký tài khoản mới
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'name' => 'nullable|string|max:255',
        ]);

        // Check exist user ?
        if (User::where('email', $request->email)->exists()) {
            return response(['message' => 'User already exists'], 409);
        }

        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'name' => $request->name,
            'role' => 'user',
        ]);

        // Trả về token cho user sau khi đăng ký
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Đăng nhập
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // Xóa token cũ trước khi tạo token mới
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    // Tự đổi mật khẩu
    public function changePassword(Request $request)
    {
        // Kiểm tra yêu cầu đầu vào
        $request->validate([
            'old_password' => ['required', 'current_password'], // Sử dụng rule current_password
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Lấy thông tin user đang đăng nhập
        $user = Auth::user();

        // Cập nhật mật khẩu mới
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Mật khẩu đã được thay đổi thành công.']);
    }

    // Đăng xuất (xóa token)
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'message' => 'Logout successful'
        ]);
    }

    // Lấy thông tin người dùng khi đã đăng nhập
    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }

    // Xóa tài khoản của chính mình (user tự xóa)
    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete(); // Xóa tất cả token trước khi xóa user
        $user->delete();

        return response()->json([
            'message' => 'Your account has been deleted successfully'
        ], 200);
    }


}
