<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Models\Machine;
use App\Models\MaintenancePlan;
use App\Models\ProductionRecord;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $factoryId = $request->user()->factory_id;

        // Machine status counts
        $machines        = Machine::where('factory_id', $factoryId)->get();
        $machineStats    = [
            'total'       => $machines->count(),
            'online'      => $machines->where('status', 'online')->count(),
            'offline'     => $machines->where('status', 'offline')->count(),
            'error'       => $machines->where('status', 'error')->count(),
            'maintenance' => $machines->where('status', 'maintenance')->count(),
        ];

        // Today's production
        $todayProduction = ProductionRecord::whereHas('machine', fn($q) => $q->where('factory_id', $factoryId))
            ->whereDate('production_date', today())
            ->selectRaw('SUM(units_produced) as total_produced, SUM(units_rejected) as total_rejected, SUM(target_units) as total_target')
            ->first();

        // Maintenance
        $maintenanceStats = [
            'overdue'    => MaintenancePlan::whereHas('machine', fn($q) => $q->where('factory_id', $factoryId))->overdue()->count(),
            'upcoming'   => MaintenancePlan::whereHas('machine', fn($q) => $q->where('factory_id', $factoryId))->upcoming()->count(),
            'in_progress'=> MaintenancePlan::whereHas('machine', fn($q) => $q->where('factory_id', $factoryId))->where('status','in_progress')->count(),
        ];

        // Active workers today
        $activeWorkers = Shift::where('factory_id', $factoryId)
            ->whereDate('shift_date', today())
            ->where('status', 'active')
            ->count();

        // Unread alerts
        $unreadAlerts = Alert::where('factory_id', $factoryId)->unread()->count();
        $criticalAlerts = Alert::where('factory_id', $factoryId)->critical()->unread()->count();

        // Recent alerts
        $recentAlerts = Alert::where('factory_id', $factoryId)
            ->with('machine')
            ->latest()
            ->limit(5)
            ->get();

        // Machine list with status
        $machineList = Machine::where('factory_id', $factoryId)
            ->where('is_active', true)
            ->select('id','name','code','type','status','location','target_output_per_hour')
            ->get();

        // Production chart (last 7 days)
        $productionChart = ProductionRecord::whereHas('machine', fn($q) => $q->where('factory_id', $factoryId))
            ->where('production_date', '>=', now()->subDays(7))
            ->selectRaw('production_date, SUM(units_produced) as units')
            ->groupBy('production_date')
            ->orderBy('production_date')
            ->get();

        return response()->json([
            'machine_stats'     => $machineStats,
            'production_today'  => $todayProduction,
            'maintenance'       => $maintenanceStats,
            'active_workers'    => $activeWorkers,
            'unread_alerts'     => $unreadAlerts,
            'critical_alerts'   => $criticalAlerts,
            'recent_alerts'     => $recentAlerts,
            'machines'          => $machineList,
            'production_chart'  => $productionChart,
        ]);
    }
}
