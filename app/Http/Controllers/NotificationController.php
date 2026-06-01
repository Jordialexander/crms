<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->unreadNotifications()
            ->whereRaw(
                "JSON_UNQUOTE(JSON_EXTRACT(data, '$.cr_id')) IN (SELECT id FROM change_requests WHERE deleted_at IS NULL)"
            )
            ->latest()
            ->paginate(20);
        return view('notifications.index', [
            'title' => 'Notifikasi',
            'breadcrumbs' => ['Dashboard' => route('dashboard'), 'Notifikasi' => '#'],
            'notifications' => $notifications,
        ]);
    }

    public function readAll(Request $request)
    {
        Auth::user()->unreadNotifications->markAsRead();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function open(Request $request, string $id)
    {
        /** @var DatabaseNotification|null $notification */
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        if (!$notification) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'not found'], 404);
            }
            return redirect()->route('notifications.index')->with('error', 'Notifikasi tidak ditemukan.');
        }

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        $redirectTo = (string) $request->input('redirect_to', route('notifications.index'));
        if (!str_starts_with($redirectTo, '/')) {
            $redirectTo = route('notifications.index');
        }

        return redirect($redirectTo);
    }
}

