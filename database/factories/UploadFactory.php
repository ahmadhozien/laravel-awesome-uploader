<?php

namespace Database\Factories;

use Hozien\Uploader\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadFactory extends Factory
{
    protected $model = Upload::class;

    public function definition()
    {
        $imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $documentTypes = ['application/pdf', 'text/plain', 'application/msword'];
        $allTypes = array_merge($imageTypes, $documentTypes);

        $type = $this->faker->randomElement($allTypes);
        $isImage = in_array($type, $imageTypes);

        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/msword' => 'doc'
        ];

        $extension = $extensions[$type];
        $filename = $this->faker->slug() . '.' . $extension;

        return [
            'path' => 'uploads/' . $filename,
            'url' => '/storage/uploads/' . $filename,
            'type' => $type,
            'name' => $filename,
            'size' => $this->faker->numberBetween(1024, 1024 * 1024), // 1KB - 1MB
            'file_hash' => $this->faker->md5(),
            'user_id' => null, // Will be set by tests as needed
            'guest_token' => null,
            'created_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'updated_at' => now(),
        ];
    }

    public function image()
    {
        return $this->state(function (array $attributes) {
            $imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $type = $this->faker->randomElement($imageTypes);

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif'
            ];

            $extension = $extensions[$type];
            $filename = $this->faker->slug() . '.' . $extension;

            return [
                'type' => $type,
                'name' => $filename,
                'path' => 'uploads/' . $filename,
                'url' => '/storage/uploads/' . $filename,
            ];
        });
    }

    public function document()
    {
        return $this->state(function (array $attributes) {
            $documentTypes = ['application/pdf', 'text/plain', 'application/msword'];
            $type = $this->faker->randomElement($documentTypes);

            $extensions = [
                'application/pdf' => 'pdf',
                'text/plain' => 'txt',
                'application/msword' => 'doc'
            ];

            $extension = $extensions[$type];
            $filename = $this->faker->slug() . '.' . $extension;

            return [
                'type' => $type,
                'name' => $filename,
                'path' => 'uploads/' . $filename,
                'url' => '/storage/uploads/' . $filename,
            ];
        });
    }

    public function forUser($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
                'guest_token' => null,
            ];
        });
    }

    public function forGuest($guestToken)
    {
        return $this->state(function (array $attributes) use ($guestToken) {
            return [
                'user_id' => null,
                'guest_token' => $guestToken,
            ];
        });
    }
}
