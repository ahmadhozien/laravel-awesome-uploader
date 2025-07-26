<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Hozien\Uploader\Models\Upload;
use Hozien\Uploader\Uploader;

class UploaderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test storage disk
        Storage::fake('testing');
        Config::set('uploader.disk', 'testing');
        Config::set('uploader.save_to_db', true);
        Config::set('uploader.allowed_file_types', ['jpg', 'png', 'pdf', 'txt']);
        Config::set('uploader.max_size', 1024); // 1MB for testing
    }

    /** @test */
    public function it_can_upload_a_single_file()
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        $response = $this->postJson('/api/uploader/upload', [
            'file' => $file,
            'saveToDb' => true
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'path',
                'url',
                'type',
                'name',
                'size',
                'file_hash',
                'is_duplicate'
            ]);

        $this->assertEquals(true, $response->json('success'));
        $this->assertDatabaseHas('uploads', [
            'name' => 'test.jpg',
            'type' => 'image/jpeg'
        ]);
    }

    /** @test */
    public function it_can_upload_multiple_files()
    {
        $files = [
            UploadedFile::fake()->image('test1.jpg'),
            UploadedFile::fake()->image('test2.png'),
        ];

        $response = $this->postJson('/api/uploader/upload', [
            'files' => $files,
            'multiple' => true,
            'saveToDb' => true
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(2);

        $this->assertDatabaseCount('uploads', 2);
    }

    /** @test */
    public function it_validates_file_types()
    {
        $file = UploadedFile::fake()->create('test.exe', 100);

        $response = $this->postJson('/api/uploader/upload', [
            'file' => $file,
            'saveToDb' => true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_validates_file_size()
    {
        $file = UploadedFile::fake()->create('large.jpg', 2048); // 2MB

        $response = $this->postJson('/api/uploader/upload', [
            'file' => $file,
            'saveToDb' => true
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function it_detects_duplicate_files()
    {
        Config::set('uploader.check_duplicates', true);

        $file = UploadedFile::fake()->image('test.jpg', 100, 100);

        // Upload first time
        $response1 = $this->postJson('/api/uploader/upload', [
            'file' => $file,
            'saveToDb' => true
        ]);

        // Upload same file again
        $response2 = $this->postJson('/api/uploader/upload', [
            'file' => $file,
            'saveToDb' => true
        ]);

        $response1->assertStatus(200);
        $response2->assertStatus(200);

        $this->assertEquals(false, $response1->json('is_duplicate'));
        $this->assertEquals(true, $response2->json('is_duplicate'));

        // Should only have one record in database
        $this->assertDatabaseCount('uploads', 1);
    }

    /** @test */
    public function it_can_fetch_uploads_with_pagination()
    {
        Upload::factory()->count(25)->create();

        $response = $this->getJson('/api/uploader/uploads?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'per_page',
                'total'
            ]);

        $this->assertCount(10, $response->json('data'));
    }

    /** @test */
    public function it_can_filter_uploads_by_type()
    {
        Upload::factory()->create(['type' => 'image/jpeg']);
        Upload::factory()->create(['type' => 'application/pdf']);
        Upload::factory()->create(['type' => 'image/png']);

        $response = $this->getJson('/api/uploader/uploads?type=images');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function it_can_search_uploads()
    {
        Upload::factory()->create(['name' => 'important_document.pdf']);
        Upload::factory()->create(['name' => 'vacation_photo.jpg']);
        Upload::factory()->create(['name' => 'another_file.txt']);

        $response = $this->getJson('/api/uploader/uploads?search=important');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('important_document.pdf', $response->json('data.0.name'));
    }

    /** @test */
    public function it_can_delete_uploads()
    {
        $upload = Upload::factory()->create();

        $response = $this->deleteJson("/api/uploader/uploads/{$upload->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // With soft deletes enabled, record should be soft deleted
        $this->assertSoftDeleted('uploads', ['id' => $upload->id]);
    }

    /** @test */
    public function it_can_get_upload_statistics()
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        Upload::factory()->count(5)->create(['user_id' => $user->id, 'type' => 'image/jpeg']);
        Upload::factory()->count(3)->create(['user_id' => $user->id, 'type' => 'application/pdf']);

        $response = $this->getJson('/api/uploader/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_files',
                'total_size',
                'total_size_formatted',
                'image_count',
                'document_count'
            ]);

        $this->assertEquals(8, $response->json('total_files'));
        $this->assertEquals(5, $response->json('image_count'));
        $this->assertEquals(3, $response->json('document_count'));
    }

    /** @test */
    public function it_validates_guest_upload_limits()
    {
        Config::set('uploader.allow_guests', true);
        Config::set('uploader.guest_upload_limit', 2);

        $guestToken = 'test-guest-token';

        // Create 2 existing uploads for guest
        Upload::factory()->count(2)->create(['guest_token' => $guestToken]);

        $file = UploadedFile::fake()->image('test.jpg');

        $response = $this->postJson('/api/uploader/upload', [
            'file' => $file,
            'guest_token' => $guestToken,
            'saveToDb' => true
        ]);

        $response->assertStatus(429)
            ->assertJsonFragment(['Guest upload limit exceeded']);
    }

    /** @test */
    public function uploader_class_can_get_file_info()
    {
        $uploader = new Uploader();
        $file = UploadedFile::fake()->image('test.jpg', 200, 300);

        $info = $uploader->getFileInfo($file);

        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('size', $info);
        $this->assertArrayHasKey('type', $info);
        $this->assertArrayHasKey('extension', $info);
        $this->assertArrayHasKey('is_image', $info);
        $this->assertArrayHasKey('human_size', $info);

        $this->assertEquals('test.jpg', $info['name']);
        $this->assertEquals('jpg', $info['extension']);
        $this->assertTrue($info['is_image']);
    }

    /** @test */
    public function uploader_class_can_validate_files()
    {
        $uploader = new Uploader();

        // Valid file
        $validFile = UploadedFile::fake()->image('test.jpg', 100, 100);
        $validation = $uploader->validateFile($validFile);

        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);

        // Invalid file type
        $invalidFile = UploadedFile::fake()->create('test.exe', 100);
        $validation = $uploader->validateFile($invalidFile);

        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }

    /** @test */
    public function uploader_class_can_format_bytes()
    {
        $uploader = new Uploader();

        $this->assertEquals('1 KB', $uploader->formatBytes(1024));
        $this->assertEquals('1 MB', $uploader->formatBytes(1024 * 1024));
        $this->assertEquals('1.5 KB', $uploader->formatBytes(1536));
    }

    /** @test */
    public function it_handles_unauthorized_access()
    {
        $upload = Upload::factory()->create(['user_id' => 999]);

        $response = $this->deleteJson("/api/uploader/uploads/{$upload->id}");

        $response->assertStatus(403)
            ->assertJsonFragment(['Unauthorized']);
    }
}
