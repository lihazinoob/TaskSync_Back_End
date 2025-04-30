<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;

class NotificationController extends Controller
{
    // Function for fething all the Notifications
    public function fetchNotifications()
    {
        $user = Auth::user();

        $notifications = Notification::where('user_id', $user->id)
            ->with(['project' => function ($query) use ($user) {
                $query->with(['users' => function ($query) use ($user) {
                    $query->where('users.id', $user->id)->withPivot('status');
                }]);
            }])->get()->map(function ($notification) {
                $status = $notification->type === 'invitation' && $notification->project && $notification->project->users->isNotEmpty()
                    ? $notification->project->users->first()->pivot->status
                    : null;

                return [
                    'id' => $notification->id,
                    'user_id' => $notification->user_id,
                    'project_id' => $notification->project_id,
                    'type' => $notification->type,
                    'message' => $notification->message,
                    'read' => $notification->read,
                    'status' => $status,
                    'created_at' => $notification->created_at,
                    'updated_at' => $notification->updated_at,
                ];
            });
        return response()->json([
            'message' => 'All the notification of the user is retrieved',
            'notifications' => $notifications
        ], 200);
    }
}
