<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_picture',
        'github_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */




    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    //  A user can create Many Project under his name
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    // Defining a many to many relationship between a user and a project.
    // Because a user can be connected to many project and a project can have many users.
    public function assignedProjects()
    {
        return $this->belongsToMany(Project::class, 'project_users')->withPivot('status')->withTimestamps();

    }

    // Defining a one to many relationship between user and notification. A user can have many notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    // Required by JWTSubject
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Add custom claims to the JWT payload (optional)
    public function getJWTCustomClaims()
    {
        return [
            'email' => $this->email,
        ];
    }
}
