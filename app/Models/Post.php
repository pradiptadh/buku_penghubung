<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['signed_at' => 'datetime'];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'target_student_id');
    }

    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }

    public function views(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    public function scopeMassal(Builder $query): Builder
    {
        return $query->whereNull('target_student_id');
    }

    public function scopePribadi(Builder $query): Builder
    {
        return $query->whereNotNull('target_student_id');
    }
}
