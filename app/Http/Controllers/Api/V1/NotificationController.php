<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * List notifications visible to the authenticated user (own + tenant broadcasts).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:all,read,unread'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        /** @var User $user */
        $user = auth('api')->user();

        $status = $request->query('status', 'all');
        $perPage = (int) $request->query('per_page', 10);

        $query = Notification::query()
            ->visibleTo($user)
            ->when($status === 'unread', fn ($query) => $query->unread())
            ->when($status === 'read', fn ($query) => $query->whereNotNull('read_at'))
            ->latest();

        $notifications = $query->paginate($perPage);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
            ],
        ]);
    }

    /**
     * Return the count of unread notifications for the authenticated user.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $count = Notification::query()
            ->visibleTo($user)
            ->unread()
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Request $request, Notification $notification): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        if ((string) $notification->tenant_id !== (string) $user->tenant_id
            || ($notification->user_id !== null && $notification->user_id !== $user->id)) {
            return response()->json([
                'message' => 'No tienes acceso a esta notificación.',
            ], 403);
        }

        if (! $notification->isRead()) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'data' => $notification->fresh(),
        ]);
    }

    /**
     * Mark every notification visible to the authenticated user as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        Notification::query()
            ->visibleTo($user)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Todas las notificaciones se marcaron como leídas.',
        ]);
    }
}
