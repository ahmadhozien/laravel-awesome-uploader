<?php

namespace Hozien\Uploader\Policies;

use Hozien\Uploader\Models\Upload;
use Illuminate\Contracts\Auth\Authenticatable as User;

class UploadPolicy
{
    public function view(User $user, Upload $upload)
    {
        return $this->isAdmin($user) || $upload->user_id === $user->id;
    }

    public function delete(User $user, Upload $upload)
    {
        return $this->isAdmin($user) || $upload->user_id === $user->id;
    }

    public function restore(User $user, Upload $upload)
    {
        return $this->isAdmin($user);
    }

    protected function isAdmin($user)
    {
        return property_exists($user, 'is_admin') && $user->is_admin;
    }
} 