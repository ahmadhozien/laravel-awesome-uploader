# Changelog

All notable changes to `laravel-awesome-uploader` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned Features

- Virus scanning integration
- Background job processing for large files
- Cloud storage optimization features
- Advanced analytics and reporting
- WebP image format support
- Bulk operations API
- Advanced permission systems

## [1.0.2] - 2025-07-26

### 🐛 Bug Fixes

- **Fixed JSON Parsing Error**: Resolved "JSON.parse: unexpected character" error when server returns non-JSON responses
- **Fixed Route URL Mismatch**: Corrected hardcoded API URLs in JavaScript components (Vue, React, popup.js)
- **Fixed Pagination Data Extraction**: Fixed file manager not displaying files due to incorrect handling of paginated API responses
- **Enhanced Error Handling**: Added comprehensive error handling and debugging for better troubleshooting
- **Added JSON Response Middleware**: Created `EnsureJsonResponse` middleware to guarantee JSON responses from API endpoints

### 🔧 Technical Improvements

- **Enhanced JavaScript Debugging**: Added detailed console logging for API requests and responses
- **Improved Error Messages**: Better error messages for debugging upload and file management issues
- **Fixed Guest Token Handling**: Improved guest token validation and permission checking
- **Added Response Validation**: Better handling of different response formats and error states

### 📝 Documentation

- **Updated API Documentation**: Corrected API endpoint URLs in documentation
- **Added Debugging Guide**: Comprehensive troubleshooting guide for common issues
- **Enhanced Error Handling Documentation**: Better documentation for error scenarios

### 🔍 Debugging Features

- **Console Logging**: Added detailed logging for API requests, responses, and file operations
- **Permission Debugging**: Added debug information for permission checking
- **Response Format Validation**: Better handling of paginated vs flat array responses

### 🔧 Additional Fixes

- **Fixed Guest User Permissions**: Resolved issue where guest users couldn't access options for their uploads
- **Fixed Delete Authorization**: Updated destroy method to properly handle guest user authorization
- **Fixed Clipboard API Error**: Added fallback clipboard functionality for non-secure contexts (HTTP domains)
- **Enhanced Database Storage**: Changed default `save_to_db` setting to `true` for better guest upload support
- **Improved Token Debugging**: Added comprehensive debug information for guest token matching and comparison
- **Fixed Console Logging**: Made frontend console logging configurable via `UPLOADER_ENABLE_LOGGING` environment variable (defaults to `false`)
- **Fixed Intervention Image Compatibility**: Added comprehensive support for both Intervention Image v2.x and v3.x with robust API method compatibility using method existence checks, proper type casting, graceful fallbacks, and correct usage of v3 encoding interfaces and methods
- **Added Thumbnail Links Feature**: Added ability to copy thumbnail URLs for images with multiple size options (150px, 300px, 600px) through the file options menu

## [1.0.0] - 2025-07-01

### 🚀 Production-Ready Enterprise Features

A comprehensive file uploader for Laravel with enterprise-level features including deduplication, thumbnails, advanced security, and performance optimizations.

### Added

- **File Deduplication**: Intelligent duplicate detection using MD5 hashing
- **Automatic Thumbnails**: Multiple thumbnail sizes generated automatically for images
- **Enhanced Image Processing**: Improved image optimization with graceful degradation when intervention/image is not installed
- **Advanced Security**: Multi-layer file validation and MIME type checking
- **Guest Upload Support**: Allow non-authenticated users with rate limiting
- **Upload Statistics**: Built-in analytics for file uploads and storage usage
- **File Cleanup**: Automatic detection and cleanup of orphaned files
- **Comprehensive Error Handling**: Robust error handling with detailed logging
- **Performance Optimizations**: Database indexing and efficient file operations
- **Enhanced Configuration**: Environment variable support for all major settings
- **Complete Test Suite**: Feature and unit tests for all functionality
- **Pagination Support**: Efficient pagination for large file lists
- **Advanced Filtering**: Search and filter uploads by type, name, date
- **File Hash Tracking**: MD5 hash storage for duplicate detection
- **Thumbnail Management**: Automatic thumbnail generation and cleanup
- **Enhanced Validation**: Content-based validation beyond MIME types
- **Comprehensive API**: RESTful API with full CRUD operations
- **Console Commands**: Package management and maintenance commands
- **CI/CD Integration**: GitHub Actions for automated testing and releases

### Core Features

- **Multiple Frontend Support**: Blade, React, and Vue components out of the box
- **Configurable Storage**: Use any of Laravel's filesystem disks (local, S3, etc.)
- **Database Integration**: Optional database storage with soft deletes
- **Policy-Driven Permissions**: Secure, customizable access control
- **Drag & Drop UI**: Modern drag-and-drop interface for all frontend components
- **Multi-File Uploads**: Support for single and multiple file uploads
- **File Type Validation**: Comprehensive file type and size validation
- **Smart User/Admin Management**: Advanced user/admin filtering with customizable access control

### Enhanced Components

- **Core Uploader Class**: Feature-rich utility class with comprehensive methods
- **Storage Trait**: Robust storage handling with error management and deduplication
- **Image Processor**: Graceful handling of image processing with or without intervention/image
- **Upload Controller**: Separated logic for single/multiple uploads with enhanced error handling
- **Database Model**: Complete model with relationships, scopes, and computed attributes
- **Configuration System**: Highly configurable with environment variables
- **Frontend Components**: Enhanced error handling and comprehensive event system

### Environment Variables

- `UPLOADER_DISK` - Storage disk configuration
- `UPLOADER_SAVE_TO_DB` - Enable/disable database storage
- `UPLOADER_CHECK_DUPLICATES` - Enable/disable duplicate detection
- `UPLOADER_IMAGE_OPTIMIZATION` - Enable/disable image optimization
- `UPLOADER_GENERATE_THUMBNAILS` - Enable/disable thumbnail generation
- `UPLOADER_STRICT_MIME` - Enable strict MIME validation
- `UPLOADER_GUEST_LIMIT` - Set guest upload limits
- `UPLOADER_PAGINATION_LIMIT` - Set pagination limits
- `UPLOADER_ENABLE_LOGGING` - Enable detailed logging
- And many more for complete customization

### API Endpoints

- `POST /api/uploader/upload` - Upload single or multiple files
- `GET /api/uploader/uploads` - List uploads with pagination and filtering
- `DELETE /api/uploader/uploads/{id}` - Delete specific upload
- `GET /api/uploader/stats` - Upload statistics (authenticated users)
- `POST /api/uploader/cleanup` - Clean orphaned files (admin only)

### Console Commands

- `php artisan uploader:status` - Show package status and configuration
- `php artisan uploader:cleanup` - Clean up orphaned files
- `php artisan uploader:thumbnails` - Generate missing thumbnails

### Security Features

- Multi-layer file validation (extension, MIME type, content)
- Filename sanitization and length limits
- Guest upload rate limiting
- Enhanced permission system with policies
- Comprehensive security logging
- Content-based MIME type validation

### Performance Features

- Database indexing for faster queries
- Efficient pagination for large datasets
- Optimized duplicate detection algorithms
- Thumbnail generation optimization
- Query optimization with eager loading
- Unique filename generation to prevent conflicts

### Developer Experience

- Comprehensive test suite (Feature + Unit tests)
- Rich utility methods in core classes
- Detailed error messages and logging
- Complete inline documentation
- Factory classes for testing
- Migration helpers
- CI/CD automation with GitHub Actions
- Professional package structure

### JavaScript Events

Enhanced event system for frontend integration:

- `files-selected` - Files selected for upload
- `upload-start` - Upload process begins
- `upload-success` - Upload completed successfully
- `upload-error` - Upload failed
- `upload-progress` - Upload progress update
- `duplicate-detected` - Duplicate file detected
- `thumbnail-generated` - Thumbnail generation complete

---

## Installation & Usage

### Quick Start

```bash
# Install the package
composer require hozien/laravel-awesome-uploader

# Publish assets
php artisan vendor:publish --provider="Hozien\Uploader\UploaderServiceProvider"

# Run migrations
php artisan migrate

# Check status
php artisan uploader:status
```

### Basic Blade Usage

```blade
<!-- Add upload button -->
<button onclick="window.dispatchEvent(new Event('open-uploader'))">
    Upload Files
</button>

<!-- Include uploader component -->
<x-uploader::popup :saveToDb="true" :multiple="true" />

<!-- Include required JS -->
<script src="{{ asset('vendor/uploader/popup.js') }}"></script>

<!-- Handle upload events -->
<script>
    window.addEventListener("upload-success", (event) => {
        const response = event.detail.response;
        console.log("Upload successful:", response);

        if (response.is_duplicate) {
            console.log("File was a duplicate");
        }

        if (response.thumbnails) {
            console.log("Thumbnails generated:", response.thumbnails);
        }
    });
</script>
```

## Requirements

- PHP 8.1+
- Laravel 9.0+
- Optional: `intervention/image` for full image processing features

## Support

- **Documentation**: Complete README with examples
- **Issues**: [GitHub Issues](https://github.com/hozien/laravel-awesome-uploader/issues)
- **Discussions**: [GitHub Discussions](https://github.com/hozien/laravel-awesome-uploader/discussions)

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover any security-related issues, please email security@example.com instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
