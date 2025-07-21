<?php

namespace Hozien\Uploader\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Upload extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'path',
        'url',
        'type',
        'name',
        'size',
        'user_id'
    ];
}
