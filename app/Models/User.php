<?php

namespace App\Models;

use App\Models\Worlds\World;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\File;
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
    public const WORLDS_SORT_ALPHABET = 'alphabet';

    public const WORLDS_SORT_CREATED_AT = 'created_at';

    public const WORLDS_SORT_UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name',
        'display_name',
        'bio',
        'avatar_path',
        'email',
        'password',
        'folder_token',
        'worlds_list_sort',
        'maps_default_width',
        'maps_default_height',
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

    public function worlds(): HasMany
    {
        return $this->hasMany(World::class);
    }

    /**
     * Ширина по умолчанию для новых карт (px): аккаунт и создание мира.
     */
    public function mapsDefaultWidth(): int
    {
        return (int) ($this->maps_default_width ?? World::MAPS_DEFAULT_SIDE_PX);
    }

    /**
     * Высота по умолчанию для новых карт (px): аккаунт и создание мира.
     */
    public function mapsDefaultHeight(): int
    {
        return (int) ($this->maps_default_height ?? World::MAPS_DEFAULT_SIDE_PX);
    }

    public function getUploadsPath(string $subpath = ''): string
    {
        $base = public_path('uploads/users/'.$this->folder_token);

        return $subpath ? $base.'/'.trim($subpath, '/') : $base;
    }

    public function getUploadsUrl(string $subpath = ''): string
    {
        $base = 'uploads/users/'.$this->folder_token;

        return asset($base.($subpath ? '/'.trim($subpath, '/') : ''));
    }

    /**
     * Создаёт подкаталог в public/uploads/users/{folder_token}/… при отсутствии
     *
     * Учётная запись хранит файлы под public/uploads; веб-процесс должен иметь право записи
     * на каталог public/uploads (типично владелец www-data).
     *
     * @param  string  $subpath  Путь относительно каталога пользователя (например profile, worlds)
     */
    public function ensureUserUploadsDirectory(string $subpath): void
    {
        $dir = $this->getUploadsPath($subpath);
        if (File::isDirectory($dir)) {
            return;
        }
        if (! File::makeDirectory($dir, 0755, true)) {
            throw new \RuntimeException(
                'Не удалось создать каталог для файлов: '.$dir.'. Проверьте владельца и права на public/uploads (нужна запись для пользователя веб-сервера, например www-data).'
            );
        }
    }

    public function avatarUrl(): ?string
    {
        if (empty($this->avatar_path)) {
            return null;
        }

        return $this->getUploadsUrl($this->avatar_path);
    }

    /**
     * Имя для отображения в интерфейсе (если не задано — учётное имя).
     */
    public function displayNameOrName(): string
    {
        $display = trim((string) $this->display_name);

        return $display !== '' ? $display : $this->name;
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
