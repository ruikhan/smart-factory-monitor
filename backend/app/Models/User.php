<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name','email','password','factory_id',
        'role','employee_id','department','is_active'
    ];

    protected $hidden = ['password','remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    public function factory(): BelongsTo    { return $this->belongsTo(Factory::class); }
    public function shifts(): HasMany       { return $this->hasMany(Shift::class); }
    public function machineLogs(): HasMany  { return $this->hasMany(MachineLog::class); }

    public function isAdmin(): bool      { return $this->role === 'super_admin'; }
    public function isManager(): bool    { return in_array($this->role, ['super_admin','manager']); }
}
