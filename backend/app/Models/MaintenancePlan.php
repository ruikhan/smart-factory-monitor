<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenancePlan extends Model
{
    protected $fillable = [
        'machine_id','assigned_to','created_by','title','description',
        'type','priority','status','scheduled_at','started_at',
        'completed_at','estimated_duration_hours','completion_notes','cost'
    ];
    protected $casts = [
        'scheduled_at'  => 'datetime',
        'started_at'    => 'datetime',
        'completed_at'  => 'datetime',
        'cost'          => 'decimal:2',
    ];

    public function machine(): BelongsTo    { return $this->belongsTo(Machine::class); }
    public function assignedTo(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }

    public function scopeOverdue($q)
    {
        return $q->where('status', 'scheduled')
                 ->where('scheduled_at', '<', now());
    }
    public function scopeUpcoming($q)
    {
        return $q->where('status', 'scheduled')
                 ->where('scheduled_at', '>=', now());
    }
}
