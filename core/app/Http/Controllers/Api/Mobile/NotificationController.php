<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Constants\Status;
use App\Http\Resources\Mobile\NotificationResource;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationController extends ApiMobileController
{
    public function index(Request $request)
    {
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $notifications = NotificationLog::query()
            ->where('user_id', $request->user()->id)
            ->latest('id')
            ->paginate((int) $request->input('per_page', 10));

        return $this->ok([
            'data' => NotificationResource::collection($notifications->getCollection())->resolve(),
            'meta' => $this->meta($notifications),
        ]);
    }

    public function poll(Request $request)
    {
        $userId = $request->user()->id;

        $notifications = NotificationLog::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->take(8)
            ->get();

        return $this->ok([
            'status' => 'success',
            'unread_count' => NotificationLog::query()->where('user_id', $userId)->where('user_read', Status::NO)->count(),
            'notifications' => NotificationResource::collection($notifications)->resolve(),
        ]);
    }

    public function read(Request $request, int $id)
    {
        $notification = NotificationLog::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        if ((int) $notification->user_read === Status::NO) {
            $notification->user_read = Status::YES;
            $notification->save();
        }

        return $this->message('Notification marked as read');
    }

    public function readAll(Request $request)
    {
        NotificationLog::query()
            ->where('user_id', $request->user()->id)
            ->where('user_read', Status::NO)
            ->update(['user_read' => Status::YES]);

        return $this->message('All notifications marked as read');
    }
}
