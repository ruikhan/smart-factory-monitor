<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Factory extends Model
{
    protected $fillable = ['name','code','location','industry_type','description','is_active'];
    protected $casts    = ['is_active' => 'boolean'];

    public function machines(): HasMany  { return $this->hasMany(Machine::class); }
    public function workers(): HasMany   { return $this->hasMany(User::class); }
    public function shifts(): HasMany    { return $this->hasMany(Shift::class); }
    public function alerts(): HasMany    { return $this->hasMany(Alert::class); }
}
