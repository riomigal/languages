<?php

namespace Riomigal\Languages\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends BaseController
{
    public function index(): JsonResponse
    {
        $notifications = $this->authUser()->unreadNotifications()->get()->map(function (DatabaseNotification $notification): array {
            $stored = $notification->getAttribute('data');
            $data = is_array($stored) ? $stored : [];
            return [
                'id' => $notification->id,
                'message' => is_string($data['message'] ?? null) ? strip_tags($data['message']) : '',
                'date_time' => is_string($data['date_time'] ?? null) ? $data['date_time'] : '',
                'read_url' => route('languages.notifications.read', ['id' => $notification->id]),
            ];
        });
        return response()->json(['notifications' => $notifications]);
    }

    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $this->authUser()->notifications()->find($id);
        abort_if($notification === null, 403);
        $request->validate(['read' => 'sometimes|boolean']);
        if ($request->boolean('read', true)) {
            $notification->markAsRead();
        } else {
            $notification->markAsUnread();
        }
        return $this->index();
    }

    public function markAllAsRead(): JsonResponse
    {
        $this->authUser()->unreadNotifications()->update(['read_at' => now()]);
        return $this->index();
    }
}
