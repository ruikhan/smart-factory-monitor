<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $shifts = Shift::where('factory_id', $request->user()->factory_id)
            ->with(['worker:id,name,employee_id', 'machine:id,name,code', 'supervisor:id,name'])
            ->when($request->date, fn($q) => $q->whereDate('shift_date', $request->date))
            ->when($request->shift_type, fn($q) => $q->where('shift_type', $request->shift_type))
            ->orderBy('shift_date', 'desc')
            ->orderBy('start_time')
            ->paginate(20);
        return response()->json($shifts);
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'       => 'required|exists:users,id',
            'machine_id'    => 'nullable|exists:machines,id',
            'shift_name'    => 'required|string',
            'shift_type'    => 'required|in:day,afternoon,night',
            'shift_date'    => 'required|date',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'supervisor_id' => 'nullable|exists:users,id',
        ]);

        $shift = Shift::create([
            ...$request->all(),
            'factory_id' => $request->user()->factory_id,
            'status'     => 'scheduled',
        ]);

        return response()->json(['shift' => $shift->load('worker:id,name', 'machine:id,name'), 'message' => 'Shift created.'], 201);
    }

    public function updateStatus(Request $request, Shift $shift)
    {
        $request->validate(['status' => 'required|in:scheduled,active,completed,absent']);
        $shift->update([
            'status'          => $request->status,
            'handover_notes'  => $request->handover_notes,
        ]);
        return response()->json(['shift' => $shift, 'message' => 'Shift status updated.']);
    }

    public function today(Request $request)
    {
        $shifts = Shift::where('factory_id', $request->user()->factory_id)
            ->whereDate('shift_date', today())
            ->with(['worker:id,name,employee_id,department', 'machine:id,name,code', 'supervisor:id,name'])
            ->orderBy('start_time')
            ->get();
        return response()->json(['shifts' => $shifts]);
    }
}
