<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Machine;
use App\Models\MachineLog;
use Illuminate\Http\Request;

class MachineController extends Controller
{
    public function index(Request $request)
    {
        $machines = Machine::where('factory_id', $request->user()->factory_id)
            ->where('is_active', true)
            ->with(['logs' => fn($q) => $q->latest()->limit(1)])
            ->get();
        return response()->json(['machines' => $machines]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'                   => 'required|string',
            'code'                   => 'required|string|unique:machines',
            'type'                   => 'required|string',
            'target_output_per_hour' => 'nullable|integer',
            'location'               => 'nullable|string',
        ]);

        $machine = Machine::create([
            ...$request->only(['name','code','type','model','manufacturer','serial_number','location','target_output_per_hour','installation_date','notes']),
            'factory_id' => $request->user()->factory_id,
            'status'     => 'offline',
        ]);

        return response()->json(['machine' => $machine], 201);
    }

    public function show(Request $request, Machine $machine)
    {
        $machine->load(['logs' => fn($q) => $q->latest()->limit(20), 'maintenancePlans' => fn($q) => $q->latest()->limit(5)]);
        return response()->json(['machine' => $machine]);
    }

    public function updateStatus(Request $request, Machine $machine)
    {
        $request->validate([
            'status' => 'required|in:online,offline,error,maintenance',
            'notes'  => 'nullable|string',
        ]);

        $previousStatus = $machine->status;
        $machine->update(['status' => $request->status]);

        // Log the status change
        MachineLog::create([
            'machine_id'      => $machine->id,
            'user_id'         => $request->user()->id,
            'status'          => $request->status,
            'previous_status' => $previousStatus,
            'notes'           => $request->notes,
        ]);

        // Create alert for error status
        if ($request->status === 'error') {
            Alert::create([
                'factory_id' => $machine->factory_id,
                'machine_id' => $machine->id,
                'user_id'    => $request->user()->id,
                'type'       => 'machine_error',
                'title'      => "Machine Error: {$machine->name}",
                'message'    => "Machine {$machine->name} ({$machine->code}) reported an error. Previous status: {$previousStatus}.",
                'severity'   => 'critical',
            ]);
        }

        return response()->json(['machine' => $machine, 'message' => 'Status updated successfully.']);
    }

    public function update(Request $request, Machine $machine)
    {
        $machine->update($request->only([
            'name','type','model','manufacturer','serial_number',
            'location','target_output_per_hour','notes',
            'last_maintenance_date','next_maintenance_date'
        ]));
        return response()->json(['machine' => $machine]);
    }

    public function destroy(Machine $machine)
    {
        $machine->update(['is_active' => false]);
        return response()->json(['message' => 'Machine deactivated.']);
    }
}
