<?php

namespace App\Http\Controllers;

use App\Helpers\Utility;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'company']);
        $this->middleware('decrypt_id')->only(['show', 'markAsRead', 'destroy']);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications",
     *     tags={"Notifications"},
     *     summary="Get all notifications for current user",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        try {
            $query = Notification::query();
            $query = Utility::prepareSearchQuery($query, $request, new Notification());
            $query->where('user_id', auth()->user()->id);
            $query->orderBy('id', 'desc');
            $notifications = Utility::getSearchRequestQueryResults($request, $query);

            return response()->json([
                'title' => 'Notifications',
                'sub-title' => 'Notifications fetched successfully',
                'success' => true,
                'data' => $notifications,
            ], 200);
        } catch (Exception $e) {
            throw ValidationException::withMessages(['error' => $e->getMessage()]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/unread-count",
     *     tags={"Notifications"},
     *     summary="Get unread notifications count",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function unreadCount(Request $request)
    {
        $count = Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'title' => 'Notifications',
            'sub-title' => 'Unread count fetched successfully',
            'success' => true,
            'data' => ['count' => $count],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/recent",
     *     tags={"Notifications"},
     *     summary="Get recent notifications (for navbar dropdown)",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function recent(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'title' => 'Notifications',
            'sub-title' => 'Recent notifications fetched successfully',
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
            ],
        ], 200);
    }

    /**
     * @OA\Get(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Get single notification",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request)
    {
        $notification = Notification::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'title' => 'Notification',
                'sub-title' => 'Notification not found',
                'success' => false,
            ], 404);
        }

        return response()->json([
            'title' => 'Notification',
            'sub-title' => 'Notification fetched successfully',
            'success' => true,
            'data' => $notification,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/{id}/read",
     *     tags={"Notifications"},
     *     summary="Mark notification as read",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAsRead(Request $request)
    {
        $notification = Notification::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'title' => 'Notification',
                'sub-title' => 'Notification not found',
                'success' => false,
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'title' => 'Notification',
            'sub-title' => 'Notification marked as read',
            'success' => true,
            'data' => $notification,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/notifications/mark-all-read",
     *     tags={"Notifications"},
     *     summary="Mark all notifications as read",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function markAllAsRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'title' => 'Notifications',
            'sub-title' => 'All notifications marked as read',
            'success' => true,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/{id}",
     *     tags={"Notifications"},
     *     summary="Delete a notification",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function destroy(Request $request)
    {
        $notification = Notification::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->first();

        if (!$notification) {
            return response()->json([
                'title' => 'Notification',
                'sub-title' => 'Notification not found',
                'success' => false,
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'title' => 'Notification',
            'sub-title' => 'Notification deleted successfully',
            'success' => true,
        ], 200);
    }

    /**
     * @OA\Delete(
     *     path="/api/notifications/clear-all",
     *     tags={"Notifications"},
     *     summary="Clear all notifications",
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function clearAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)
            ->where('company_id', $request->company->id)
            ->delete();

        return response()->json([
            'title' => 'Notifications',
            'sub-title' => 'All notifications cleared',
            'success' => true,
        ], 200);
    }
}
