<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Cviebrock\EloquentSluggable\Sluggable;

class Task extends Model
{   

    use HasFactory, Sluggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'user_id'
    ];

       protected $casts = [
        'due_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot method to auto-generate UUID when creating a task
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Uuid::uuid4()->toString();
            }
        });
    }
     

      public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
                'unique' => true,
                'separator' => '-',
                'max_length' => 150,
            ]
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
    public function getRouteKeyName()
    {
        return 'uuid';
    }

   public function scopeByStatus($query, $status){
     return $query->where('status', $status);
   }

   public function scopeByPriority($query, $priority){
     return $query->where('priority', $priority);
   }
  
     public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())->where('status', '!=', 'completed');
    }
}
