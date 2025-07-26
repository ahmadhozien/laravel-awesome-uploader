<?php

namespace Hozien\Uploader\Policies;

use Hozien\Uploader\Models\Upload;
use Illuminate\Contracts\Auth\Authenticatable as User;
use Illuminate\Support\Facades\Session;

class UploadPolicy
{
    public function view($user, Upload $upload, $guestToken = null)
    {
        if ($user) {
            return $this->isAdmin($user) || $upload->user_id === $user->id;
        }
        // Guest: allow if guest_token matches
        return $guestToken && $upload->guest_token === $guestToken;
    }

    public function delete($user, Upload $upload, $guestToken = null)
    {
        if ($user) {
            return $this->isAdmin($user) || $upload->user_id === $user->id;
        }
        // Guest: allow if guest_token matches
        return $guestToken && $upload->guest_token === $guestToken;
    }

    public function restore($user, Upload $upload, $guestToken = null)
    {
        if ($user) {
            return $this->isAdmin($user);
        }
        return false;
    }

    protected function isAdmin($user)
    {
        return property_exists($user, 'is_admin') && $user->is_admin;
    }
}
