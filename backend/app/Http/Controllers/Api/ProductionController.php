<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ProductionRecord;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function index(Request $request)
    {
        $records = ProductionRecord::whereHas('machine', fn($q) => $q->where('factory_id', $request->user()->factory_id))
            ->with(['machine:id,name,code', 'user:id,name'])
            ->when($request->date, fn($q) => $q->whereDate('production_date', $request->date))
            ->when($request->machine_id, fn($q) => $q->where('machine_id', $request->machine_id))
            ->latest()
            ->paginate(20);
        return response()->json($records);
    }

    public function store(Request $request)
    {
        $request->validate([
            'machine_id'      => 'required|exists:machines,id',
            'units_produced'  => 'required|integer|min:0',
            'units_rejected'  => 'nullable|integer|min:0',
            'target_units'    => 'required|integer|min:1',
            'product_name'    => 'nullable|string',
            'shift'           => 'required|in:day,afternoon,night',
            'production_date' => 'required|date',
            'start_time'      => 'required',
            'end_time'        => 'nullable',
            'notes'           => 'nullable|string',
        ]);

        $record = ProductionRecord::create([
            ...$request->all(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['record' => $record->load('machine:id,name,code'), 'message' => 'Production record saved.'], 201);
    }

    public function summary(Request $request)
    {
        $factoryId = $request->user()->factory_id;
        $period    = $request->period ?? 'today';

        $query = ProductionRecord::whereHas('machine', fn($q) => $q->where('factory_id', $factoryId));

        match($period) {
            'today'   => $query->whereDate('production_date', today()),
            'week'    => $query->whereBetween('production_date', [now()->startOfWeek(), now()->endOfWeek()]),
            'month'   => $query->whereMonth('production_date', now()->month),
            default   => $query->whereDate('production_date', today()),
        };

        $summary = $query->selectRaw('
            SUM(units_produced) as total_produced,
            SUM(units_rejected) as total_rejected,
            SUM(target_units) as total_target,
            COUNT(*) as total_records
        ')->first();

        return response()->json(['summary' => $summary, 'period' => $period]);
    }
}
