<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SafetyAlert extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'alarm_at' => 'datetime',
        'ack_at' => 'datetime',
        'muted' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function ackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ack_by');
    }
}
