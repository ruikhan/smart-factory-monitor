<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineLog extends Model
{
    protected $fillable = ['machine_id','user_id','status','previous_status','notes','metadata'];
    protected $casts    = ['metadata' => 'array'];

    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
}
