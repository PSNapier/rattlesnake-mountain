<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
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
        'role',
        'referred_by_username',
        'bio',
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
            'role' => Role::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function characterImages(): HasMany
    {
        return $this->hasMany(CharacterImage::class);
    }

    public function herds(): HasMany
    {
        return $this->hasMany(Herd::class, 'owner_id');
    }

    public function createdHerds(): HasMany
    {
        return $this->hasMany(Herd::class, 'created_by');
    }

    public function horses(): HasMany
    {
        return $this->hasMany(Horse::class, 'owner_id');
    }

    public function bredHorses(): HasMany
    {
        return $this->hasMany(Horse::class, 'bred_by');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'user_items')
            ->withPivot('quantity')
            ->withTimestamps();
    }
}
