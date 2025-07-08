<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'team_lead_id',
        'created_by',
        'is_active',
        'uuid'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($team) {
            $team->uuid = Str::uuid();
            $team->slug = Str::slug($team->name . '-' . Str::random(6));
        });
    }

    

    public function teamLead()
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->hasMany(TeamMember::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}

