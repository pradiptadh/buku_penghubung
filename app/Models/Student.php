<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $guarded = ['id'];

    protected $appends = ['initials'];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function pickups(): HasMany
    {
        return $this->hasMany(Pickup::class);
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
}
