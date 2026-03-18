<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionRecord extends Model
{
    protected $fillable = [
        'machine_id','user_id','units_produced','units_rejected',
        'target_units','product_name','shift','production_date',
        'start_time','end_time','notes'
    ];
    protected $casts = ['production_date' => 'date'];

    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }

    public function getEfficiencyAttribute(): float
    {
        if ($this->target_units <= 0) return 0;
        return round(($this->units_produced / $this->target_units) * 100, 2);
    }

    public function getDefectRateAttribute(): float
    {
        if ($this->units_produced <= 0) return 0;
        return round(($this->units_rejected / $this->units_produced) * 100, 2);
    }
}
