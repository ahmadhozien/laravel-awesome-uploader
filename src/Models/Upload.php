<?php

namespace Hozien\Uploader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Upload extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'path',
        'url',
        'type',
        'name',
        'size',
        'user_id',
        'guest_token',
        'file_hash'
    ];

    protected $casts = [
        'size' => 'integer',
        'user_id' => 'integer',
    ];

    /**
     * Get the user that owns the upload.
     */
    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'));
    }

    /**
     * Scope a query to only include image files.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', 'like', 'image%');
    }

    /**
     * Scope a query to only include document files.
     */
    public function scopeDocuments(Builder $query): Builder
    {
        return $query->where('type', 'not like', 'image%');
    }

    /**
     * Get formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        return app('uploader')->formatBytes($this->size);
    }

    /**
     * Check if file is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->type, 'image');
    }
}
