<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = [
        'factory_id','name','code','type','model','manufacturer',
        'serial_number','location','status','target_output_per_hour',
        'installation_date','last_maintenance_date','next_maintenance_date','notes','is_active'
    ];
    protected $casts = [
        'is_active'             => 'boolean',
        'installation_date'     => 'date',
        'last_maintenance_date' => 'date',
        'next_maintenance_date' => 'date',
    ];

    public function factory(): BelongsTo      { return $this->belongsTo(Factory::class); }
    public function logs(): HasMany            { return $this->hasMany(MachineLog::class); }
    public function productionRecords(): HasMany { return $this->hasMany(ProductionRecord::class); }
    public function maintenancePlans(): HasMany  { return $this->hasMany(MaintenancePlan::class); }
    public function shifts(): HasMany           { return $this->hasMany(Shift::class); }
    public function alerts(): HasMany           { return $this->hasMany(Alert::class); }

    public function getIsOnlineAttribute(): bool { return $this->status === 'online'; }
    public function scopeOnline($q)       { return $q->where('status', 'online'); }
    public function scopeOffline($q)      { return $q->where('status', 'offline'); }
    public function scopeError($q)        { return $q->where('status', 'error'); }
    public function scopeMaintenance($q)  { return $q->where('status', 'maintenance'); }
}
