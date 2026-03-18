<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'factory_id','machine_id','user_id','type','title',
        'message','severity','is_read','is_resolved','resolved_at'
    ];
    protected $casts = [
        'is_read'     => 'boolean',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public function factory(): BelongsTo { return $this->belongsTo(Factory::class); }
    public function machine(): BelongsTo { return $this->belongsTo(Machine::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }

    public function scopeUnread($q)    { return $q->where('is_read', false); }
    public function scopeCritical($q)  { return $q->where('severity', 'critical'); }
}
