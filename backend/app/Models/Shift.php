<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model
{
    protected $fillable = [
        'factory_id','machine_id','user_id','supervisor_id',
        'shift_name','shift_type','shift_date','start_time',
        'end_time','status','handover_notes'
    ];
    protected $casts = ['shift_date' => 'date'];

    public function factory(): BelongsTo    { return $this->belongsTo(Factory::class); }
    public function machine(): BelongsTo    { return $this->belongsTo(Machine::class); }
    public function worker(): BelongsTo     { return $this->belongsTo(User::class, 'user_id'); }
    public function supervisor(): BelongsTo { return $this->belongsTo(User::class, 'supervisor_id'); }
}
