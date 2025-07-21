# Laravel Awesome Uploader

A customizable and pluggable file uploader for Laravel that supports Blade, React, and Vue.

## Features

- **Any File Type**: Upload images, PDFs, documents, and more.
- **Multiple Frontend Options**: Blade, React, and Vue components out of the box.
- **Configurable Storage**: Use any of Laravel's filesystem disks (local, S3, etc.).
- **Image Processing**: Optional image optimization, resizing, and cropping (requires `intervention/image`).
- **JSON Responses**: Consistent API responses with file `path`, `url`, and `type`.
- **Drag & Drop**: Modern drag-and-drop UI for all frontend components.
- **No JS Dependencies for Blade**: The Blade uploader works out of the box with vanilla JavaScript—no Alpine.js or other libraries required.

## Installation

You can install the package via composer:

```bash
composer require hozien/laravel-awesome-uploader
```

Then, publish the package's assets:

```bash
php artisan vendor:publish --provider="Hozien\Uploader\UploaderServiceProvider"
```

This will publish the configuration file to `config/uploader.php`, views to `resources/views/vendor/uploader`, and frontend assets to `public/vendor/uploader`.

## Configuration

You can customize the uploader's behavior in the `config/uploader.php` file.

```php
// config/uploader.php

return [
    'disk' => env('UPLOADER_DISK', 'public'),
    'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
    'max_size' => 2048, // in KB
    'image_optimization' => true,
    'ui' => [
        'blade' => true,
        'react' => true,
        'vue' => true,
    ],
];
```

## Usage

### Blade

The package provides a Blade component that you can use in your views. **No Alpine.js or other JS libraries are required.**

```html
<!-- Add a button to open the uploader -->
<button onclick="window.dispatchEvent(new Event('open-uploader'))">
  Open Uploader
</button>

<!-- Include the uploader component -->
<x-uploader::popup />

<!-- Listen for the files-uploaded event -->
<script>
  window.addEventListener("files-uploaded", (event) => {
    console.log(event.detail);
  });
</script>
```

### React

To use the React component, import it into your application and provide the necessary props.

```jsx
import React from "react";
import Uploader from "../vendor/uploader/react/Uploader";

function MyComponent() {
  const handleUploadSuccess = (response) => {
    console.log("Upload successful:", response);
  };

  const handleUploadError = (error) => {
    console.error("Upload failed:", error);
  };

  return (
    <div>
      <h1>My React App</h1>
      <Uploader
        onUploadSuccess={handleUploadSuccess}
        onUploadError={handleUploadError}
      />
    </div>
  );
}

export default MyComponent;
```

### Vue

To use the Vue component, import it into your application and listen for the `upload-success` and `upload-error` events.

```vue
<template>
  <div>
    <h1>My Vue App</h1>
    <Uploader @upload-success="onUploadSuccess" @upload-error="onUploadError" />
  </div>
</template>

<script>
import Uploader from "../vendor/uploader/vue/Uploader.vue";

export default {
  components: {
    Uploader,
  },
  methods: {
    onUploadSuccess(response) {
      console.log("Upload successful:", response);
    },
    onUploadError(error) {
      console.error("Upload failed:", error);
    },
  },
};
</script>
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
