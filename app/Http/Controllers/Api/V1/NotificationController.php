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
}
