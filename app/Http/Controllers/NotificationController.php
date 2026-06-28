<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $appointmentRoute = match (true) {
            $user->isManagement() => 'management.appointments.show',
            $user->isTherapist() => 'therapist.appointments.show',
            $user->isCustomer() => 'customer.appointments.show',
            default => null,
        };

        $notifications = $user
            ->systemNotifications()
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('notifications.index', compact('appointmentRoute', 'notifications'));
    }

    public function markRead(Request $request, SystemNotification $notification): RedirectResponse
    {
        abort_unless($notification->recipient_user_id === $request->user()->id, 404);

        if (! $notification->is_read) {
            $notification->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }

        return back()->with('success', 'Notification marked as read.');
    }
}
