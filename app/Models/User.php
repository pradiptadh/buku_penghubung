<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'position', 'using_default_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class);
    }

    public function children(): HasMany
    {
        return $this->hasMany(Student::class, 'parent_user_id');
    }

    /**
     * Prototipe: satu akun ortu terhubung ke satu anak.
     */
    public function child(): ?Student
    {
        return $this->children()->first();
    }

    public function isRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Inisial avatar, paritas initials() di prototipe.
     */
    public function getInitialsAttribute(): string
    {
        $clean = preg_replace('/^(Ms\\.|Bu|Pak)\s+/u', '', $this->name);

        return mb_strtoupper(collect(preg_split('/\s+/u', trim((string) $clean)))
            ->filter()
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->take(2)
            ->implode(''));
    }

    /**
     * Password bawaan per peran (paritas defPw() di prototipe).
     */
    public static function defaultPasswordFor(string $role): string
    {
        return match ($role) {
            'admin' => 'admin123',
            'guru' => 'guru123',
            'pimpinan' => 'pimpinan123',
            default => 'ortu123',
        };
    }

    /**
     * Halaman awal sesuai peran setelah login.
     */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'guru' => 'guru.kehadiran',
            'ortu' => 'ortu.beranda',
            'pimpinan' => 'pimpinan.ringkasan',
            default => 'admin.akun',
        };
    }

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
            'using_default_password' => 'boolean',
        ];
    }
}
