<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'category',
        'techStack',
        'workType',
        'slack',
        'user_id'
    ];

    // A project belongs to only one user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // A Project can have many Tasks
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    // A project can have many users assigned to it
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_users')->withPivot('status')->withTimestamps();
    }
}
