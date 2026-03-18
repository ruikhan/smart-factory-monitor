<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\MaintenancePlan;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index(Request $request)
    {
        $plans = MaintenancePlan::whereHas('machine', fn($q) => $q->where('factory_id', $request->user()->factory_id))
            ->with(['machine:id,name,code', 'assignedTo:id,name', 'createdBy:id,name'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->orderBy('scheduled_at')
            ->paginate(20);
        return response()->json($plans);
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id'               => 'required|exists:machines,id',
            'title'                    => 'required|string',
            'type'                     => 'required|in:preventive,corrective,emergency',
            'priority'                 => 'required|in:low,medium,high,critical',
            'scheduled_at'             => 'required|date',
            'estimated_duration_hours' => 'nullable|integer',
            'assigned_to'              => 'nullable|exists:users,id',
            'description'              => 'nullable|string',
        ]);

        $plan = MaintenancePlan::create([
            ...$request->all(),
            'created_by' => $request->user()->id,
            'status'     => 'scheduled',
        ]);

        // Alert for critical/emergency
        if (in_array($request->priority, ['critical']) || $request->type === 'emergency') {
            Alert::create([
                'factory_id' => $request->user()->factory_id,
                'machine_id' => $request->machine_id,
                'type'       => 'maintenance_critical',
                'title'      => "Critical Maintenance: {$plan->title}",
                'message'    => "A critical maintenance task has been scheduled.",
                'severity'   => 'critical',
            ]);
        }

        return response()->json(['plan' => $plan->load('machine:id,name,code'), 'message' => 'Maintenance plan created.'], 201);
    }

    public function updateStatus(Request $request, MaintenancePlan $maintenancePlan)
    {
        $request->validate([
            'status'           => 'required|in:scheduled,in_progress,completed,cancelled',
            'completion_notes' => 'nullable|string',
            'cost'             => 'nullable|numeric',
        ]);

        $data = ['status' => $request->status];
        if ($request->status === 'in_progress') $data['started_at']   = now();
        if ($request->status === 'completed')   $data['completed_at'] = now();
        if ($request->completion_notes) $data['completion_notes'] = $request->completion_notes;
        if ($request->cost)             $data['cost']             = $request->cost;

        $maintenancePlan->update($data);

        return response()->json(['plan' => $maintenancePlan, 'message' => 'Maintenance status updated.']);
    }
}
