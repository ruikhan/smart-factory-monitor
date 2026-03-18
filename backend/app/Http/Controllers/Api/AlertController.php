<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $alerts = Alert::where('factory_id', $request->user()->factory_id)
            ->with(['machine:id,name,code'])
            ->when($request->severity, fn($q) => $q->where('severity', $request->severity))
            ->when($request->unread, fn($q) => $q->where('is_read', false))
            ->latest()
            ->paginate(20);
        return response()->json($alerts);
    }

    public function unreadCount(Request $request)
    {
        $count = Alert::where('factory_id', $request->user()->factory_id)->unread()->count();
        return response()->json(['unread_count' => $count]);
    }

    public function markRead(Request $request, Alert $alert)
    {
        $alert->update(['is_read' => true]);
        return response()->json(['message' => 'Alert marked as read.']);
    }

    public function markAllRead(Request $request)
    {
        Alert::where('factory_id', $request->user()->factory_id)->unread()->update(['is_read' => true]);
        return response()->json(['message' => 'All alerts marked as read.']);
    }

    public function resolve(Request $request, Alert $alert)
    {
        $alert->update(['is_resolved' => true, 'resolved_at' => now(), 'is_read' => true]);
        return response()->json(['message' => 'Alert resolved.']);
    }
}
