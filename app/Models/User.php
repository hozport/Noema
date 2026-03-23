<?php

namespace App\Models;

use App\Models\Worlds\World;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'folder_token',
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

    public function worlds(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(World::class);
    }

    public function getUploadsPath(string $subpath = ''): string
    {
        $base = public_path('uploads/users/' . $this->folder_token);
        return $subpath ? $base . '/' . trim($subpath, '/') : $base;
    }

    public function getUploadsUrl(string $subpath = ''): string
    {
        $base = 'uploads/users/' . $this->folder_token;
        return asset($base . ($subpath ? '/' . trim($subpath, '/') : ''));
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->folder_token)) {
                $user->folder_token = static::generateUniqueFolderToken();
            }
        });
    }

    protected static function generateUniqueFolderToken(): string
    {
        do {
            $token = Str::lower(Str::random(20));
        } while (static::where('folder_token', $token)->exists());

        return $token;
    }
}
