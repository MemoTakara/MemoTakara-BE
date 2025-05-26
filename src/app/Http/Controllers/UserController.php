<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    // Lấy thông tin người dùng khi đã đăng nhập
    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }

    // Tự đổi mật khẩu
    public function changePassword(Request $request)
    {
        // Kiểm tra yêu cầu đầu vào
        $request->validate([
            'old_password' => ['required', 'current_password'], // Sử dụng rule current_password
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($request->new_password === $request->old_password) {
            return response()->json(['message' => 'Same pass.'], 400);
        }

        // Lấy thông tin user đang đăng nhập
        $user = Auth::user();

        // Cập nhật mật khẩu mới
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Mật khẩu đã được thay đổi thành công.']);
    }

    // Update profile
    public function updateAccount(Request $request)
    {
        $user = Auth::user();

        // Xác thực dữ liệu đầu vào
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|nullable|string|max:255',
            'username' => 'sometimes|string|max:255|unique:users,username,' . $user->id,
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Cập nhật thông tin người dùng
        if ($request->has('name')) {
            $user->name = $request->name;
        }
        if ($request->has('username')) {
            $user->username = $request->username;
        }
        if ($request->has('email')) {
            $user->email = $request->email;
        }

        $user->save();

        return response()->json(['message' => 'Thông tin đã được cập nhật thành công.']);
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
