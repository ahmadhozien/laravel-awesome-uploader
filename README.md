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
