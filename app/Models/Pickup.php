<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pickup extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'ready_at' => 'datetime',
        'picked_at' => 'datetime',
        'reminded_at' => 'datetime',
        'otw' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
