<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'project_id',
        'title',
        'category',
        'description',
        'created_by',
        'assignees',
        'timeline',
        'status'
    ];

    protected $casts = [
        'assignees' => 'array',
        'timeline' => 'array'
    ];

    // A task belongs to only one project
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // A task can have3 many subtasks
    public function subtasks()
    {
        return $this->hasMany(Subtask::class);
    }

}
