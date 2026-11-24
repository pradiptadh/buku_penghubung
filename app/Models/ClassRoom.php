<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassRoom extends Model
{
    protected $table = 'classes';

    protected $guarded = ['id'];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'class_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'class_id');
    }

    public function schedule(): HasMany
    {
        return $this->hasMany(ScheduleSlot::class, 'class_id');
    }

    /**
     * Slot jadwal untuk satu hari kerja (1=Senin … 5=Jumat), urut jam.
     *
     * @return Collection<int, ScheduleSlot>
     */
    public function scheduleFor(int $day): Collection
    {
        return $this->schedule->where('day', $day)
            ->sortBy([['start_time', 'asc'], ['position', 'asc']])
            ->values();
    }
}
