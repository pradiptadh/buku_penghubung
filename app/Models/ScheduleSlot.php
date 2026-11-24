<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSlot extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['day' => 'integer', 'position' => 'integer'];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    /**
     * Jam tampilan "07.30" (paritas format prototipe).
     */
    public function jamLabel(): string
    {
        return str_replace(':', '.', substr((string) $this->start_time, 0, 5));
    }
}
