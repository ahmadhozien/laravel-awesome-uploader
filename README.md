# Laravel Awesome Uploader

A customizable and pluggable file uploader for Laravel that supports Blade, React, and Vue.

## Features

- **Any File Type**: Upload images, PDFs, documents, and more.
- **Multiple Frontend Options**: Blade, React, and Vue components out of the box.
- **Configurable Storage**: Use any of Laravel's filesystem disks (local, S3, etc.).
- **Image Processing**: Optional image optimization, resizing, and cropping (requires `intervention/image`).
- **JSON Responses**: Consistent API responses with file `path`, `url`, `type`, `name`, `size`, and (if enabled) `id`.
- **Drag & Drop**: Modern drag-and-drop UI for all frontend components.
- **Database Integration**: Optionally save uploads to the database and fetch them via API.
- **Soft Deletes**: Optionally enable soft deletes for uploads.
- **Smart User/Admin Fetching**: Easily fetch only the current user's uploads, or all uploads for admins.
- **Policy-Driven Permissions**: Secure, customizable access control for uploads.

## Installation

You can install the package via composer:

```bash
composer require hozien/laravel-awesome-uploader
```

Then, publish the package's assets:

```bash
php artisan vendor:publish --provider="Hozien\Uploader\UploaderServiceProvider"
```

This will publish the configuration file to `config/uploader.php`, views to `resources/views/vendor/uploader`, frontend assets (including JS) to `public/vendor/uploader`, migrations to `database/migrations`, and translation files to `resources/lang/vendor/uploader`.

## Database Setup

To enable database integration:

1. Publish the migration:
   ```bash
   php artisan vendor:publish --tag=uploader-migrations
   ```
2. Run the migration:
   ```bash
   php artisan migrate
   ```

## Configuration

You can customize the uploader's behavior in the `config/uploader.php` file.

```php
return [
    // ...
    'save_to_db' => false, // Set to true to save uploads to the database
    'soft_deletes' => false, // Set to true to enable soft deletes
    // Smart user/admin logic
    'user_resolver' => function () { return auth()->user(); },
    'admin_resolver' => function ($user) { return $user && property_exists($user, 'is_admin') && $user->is_admin; },
    'uploads_query' => function ($query, $user, $isAdmin) {
        if ($isAdmin) return $query;
        return $query->where('user_id', $user ? $user->id : null);
    },
    // ...
];
```

## Usage

### Blade

The package provides a Blade component that you can use in your views. **No Alpine.js or other JS libraries are required.**

**You must include the published JS asset for the uploader modal to work:**

```blade
<!-- Add a button to open the uploader -->
<button onclick="window.dispatchEvent(new Event('open-uploader'))">Open Uploader</button>

<!-- Include the uploader component -->
<x-uploader::popup :saveToDb="true" :multiple="true" />

<!-- Include the uploader JS asset (required) -->
<script src="{{ asset('vendor/uploader/popup.js') }}"></script>

<!-- Listen for the files-uploaded event -->
<script>
  window.addEventListener("files-uploaded", (event) => {
    console.log(event.detail);
  });
</script>
```

#### Options

- `:saveToDb="true"` — Save uploads to the database (default: false)
- `:multiple="true"` — Allow multiple file uploads (default: true)
- All labels and options are customizable via props or translation files.

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

### Fetching Uploads (API)

- **Endpoint:** `GET /api/uploads`
- **Returns:**
  - For regular users: only their uploads
  - For admins: all uploads
- **How it works:**
  - Uses the `user_resolver`, `admin_resolver`, and `uploads_query` closures in config for full flexibility.

### Soft Deletes

- Enable by setting `'soft_deletes' => true` in your config.
- Deleted uploads are only marked as deleted and can be restored.
- If false, uploads are permanently deleted.

### Policies & Permissions

- The package registers a policy for the `Upload` model.
- By default:
  - Admins can view/delete/restore any upload
  - Users can only act on their own uploads
- **To customize permissions:**
  1. Publish the policy to your app:
     ```bash
     php artisan vendor:publish --tag=uploader-policy
     ```
  2. Edit `app/Policies/UploadPolicy.php` as needed (see examples below).
  3. Register your custom policy in `AuthServiceProvider` if needed.
- **Example: Only allow users with a specific permission to delete:**
  ```php
  public function delete(User $user, Upload $upload)
  {
      return $user->hasPermissionTo('uploader-delete');
  }
  ```
- **Example: Restrict to team members:**
  ```php
  public function view(User $user, Upload $upload)
  {
      return $user->is_admin || $upload->user_id === $user->id || $user->team_id === $upload->team_id;
  }
  ```
- Use `$this->authorize('view', $upload)` or similar in your controllers.
- You can check permissions in Blade views with `@can('delete', $upload)`.

| Action  | Who Can Do It (Default) | How to Customize         |
| ------- | ----------------------- | ------------------------ |
| View    | Admins, owner           | Edit `view` in policy    |
| Delete  | Admins, owner           | Edit `delete` in policy  |
| Restore | Admins only             | Edit `restore` in policy |

**This system gives you full control and security, and is easy to override for any business logic!**

### Customizing User/Admin Logic

- Override the `

### Guest Uploads

- You can allow non-authenticated (guest) users to upload files by setting `'allow_guests' => true` in your `config/uploader.php`.
- Guest uploads are tracked by a unique guest token (session ID by default).
- Each upload will have either a `user_id` (for logged-in users) or a `guest_token` (for guests).
- When fetching uploads, guests will only see their own uploads (by session), while authenticated users and admins see their own or all uploads as configured.
- You can customize how the guest token is generated by overriding the `guest_token_resolver` closure in your config:
  ```php
  'guest_token_resolver' => function () {
      return session()->getId(); // or use a custom cookie/token
  },
  ```
- If `'allow_guests' => false`, guests will receive a 403 error if they try to upload.

### JavaScript Events

The uploader emits several custom JS events for easy integration:

| Event Name        | When It Fires                        | Payload (event.detail)   |
| ----------------- | ------------------------------------ | ------------------------ |
| files-selected    | When files are selected (input/drag) | `{ files: [File, ...] }` |
| upload-start      | When upload begins                   | `{ files: [File, ...] }` |
| upload-success    | When upload completes successfully   | `{ response: ... }`      |
| upload-error      | When upload fails                    | `{ error: ... }`         |
| add-files-clicked | When "Add Files" is clicked          | `{ selected: [...] }`    |
| modal-opened      | When the modal is opened             | `{}`                     |
| modal-closed      | When the modal is closed             | `{}`                     |

**Example usage:**

```js
window.addEventListener("files-selected", (event) => {
  console.log("Files selected:", event.detail.files);
});
window.addEventListener("upload-success", (event) => {
  console.log("Upload success:", event.detail.response);
});
window.addEventListener("add-files-clicked", (event) => {
  console.log("Add files clicked:", event.detail.selected);
});
```

You can use these events to trigger custom UI updates, analytics, or integrate with other parts of your app.
