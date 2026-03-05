<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

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
        'frozen_at',
        'banned_at',
        'last_login_at',
        'is_sanctuary',
        'referred_by_username',
        'bio',
        'avatar',
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
            'frozen_at' => 'datetime',
            'banned_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_sanctuary' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isFrozen(): bool
    {
        return $this->frozen_at !== null;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function isSanctuary(): bool
    {
        return $this->is_sanctuary === true;
    }

    public static function sanctuary(): self
    {
        $user = static::where('is_sanctuary', true)->first();

        if (! $user) {
            throw new \RuntimeException('Sanctuary user does not exist. Run the SanctuarySeeder.');
        }

        return $user;
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
