<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    // Get all notifications for authenticated user
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'nullable|string|in:collection_rated,collection_duplicated,system,achievement',
            'is_read' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();

        $query = Notification::where('user_id', $userId)
            ->with(['sender:id,name,username'])
            ->orderBy('created_at', 'desc');

        if ($request->has('type')) {
            $query->byType($request->type);
        }
        if ($request->has('is_read')) {
            if ($request->is_read) {
                $query->where('is_read', true);
            } else {
                $query->unread();
            }
        }

        $perPage = $request->get('per_page', 20);
        $notifications = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    // Get unread notifications count
    public function unreadCount(): JsonResponse
    {
        $count = Notification::where('user_id', Auth::id())
            ->unread()
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count
            ]
        ]);
    }

    // Get recent notifications (last 50)
    public function recent(): JsonResponse
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->with(['sender:id,name,username'])
            ->recent(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications
        ]);
    }

    // Mark a notification as read
    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => $notification
        ]);
    }

    // Mark multiple notifications as read
    public function markMultipleAsRead(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $updatedCount = Notification::where('user_id', Auth::id())
            ->whereIn('id', $request->notification_ids)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifications marked as read',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ]);
    }

    // Mark all notifications as read
    public function markAllAsRead(): JsonResponse
    {
        $updatedCount = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'All notifications marked as read',
            'data' => [
                'updated_count' => $updatedCount
            ]
        ]);
    }

    // Delete a notification
    public function delete($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully'
        ]);
    }

    // Delete multiple notifications
    public function deleteMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'integer|exists:notifications,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $deletedCount = Notification::where('user_id', Auth::id())
            ->whereIn('id', $request->notification_ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notifications deleted successfully',
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
    }

    // Delete all read notifications
    public function deleteAllRead(): JsonResponse
    {
        $deletedCount = Notification::where('user_id', Auth::id())
            ->where('is_read', true)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'All read notifications deleted successfully',
            'data' => [
                'deleted_count' => $deletedCount
            ]
        ]);
    }

    // Get notification statistics
    public function statistics(): JsonResponse
    {
        $userId = Auth::id();

        $stats = [
            'total' => Notification::where('user_id', $userId)->count(),
            'unread' => Notification::where('user_id', $userId)->unread()->count(),
            'read' => Notification::where('user_id', $userId)->where('is_read', true)->count(),
            'by_type' => Notification::where('user_id', $userId)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->get()
                ->pluck('count', 'type'),
            'recent_week' => Notification::where('user_id', $userId)
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
            'recent_month' => Notification::where('user_id', $userId)
                ->where('created_at', '>=', now()->subMonth())
                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    // Send notification to user (Admin only or system use)
    public function send(Request $request): JsonResponse
    {
        // Check if user is admin (you might want to implement proper authorization)
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
            'type' => 'required|string|in:system,collection_duplicated,collection_rated,study_due',
            'message' => 'required|string|max:255',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $notification = Notification::createNotification(
                $request->user_id,
                $request->type,
                $request->message,
                $request->data,
                Auth::id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => $notification
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send notification'
            ], 500);
        }
    }

    // Send bulk notifications (Admin only)
    public function sendBulk(Request $request): JsonResponse
    {
        // Check if user is admin
        if (!Auth::user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:users,id',
            'type' => 'required|string|in:system,achievement,announcement',
            'message' => 'required|string|max:255',
            'data' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $notifications = [];
            foreach ($request->user_ids as $userId) {
                $notifications[] = Notification::createNotification(
                    $userId,
                    $request->type,
                    $request->message,
                    $request->data,
                    Auth::id()
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Bulk notifications sent successfully',
                'data' => [
                    'sent_count' => count($notifications),
                    'notifications' => $notifications
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send bulk notifications'
            ], 500);
        }
    }

    // Get notification details
    public function show($id): JsonResponse
    {
        $notification = Notification::where('user_id', Auth::id())
            ->with(['sender:id,name,username'])
            ->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found'
            ], 404);
        }

        // Mark as read when viewed
        if (!$notification->is_read) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'data' => $notification
        ]);
    }
}
