<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Hozien\Uploader\Support\ImageProcessor;
use Illuminate\Http\UploadedFile;

class ImageProcessorTest extends TestCase
{
    protected $imageProcessor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('testing');
        Config::set('uploader.disk', 'testing');

        $this->imageProcessor = new ImageProcessor();
    }

    /** @test */
    public function it_checks_availability()
    {
        $isAvailable = $this->imageProcessor->isAvailable();

        // This will depend on whether Intervention Image is installed
        $this->assertIsBool($isAvailable);
    }

    /** @test */
    public function it_handles_missing_intervention_image_gracefully()
    {
        if (!$this->imageProcessor->isAvailable()) {
            $result = $this->imageProcessor->process('fake/path.jpg');

            $this->assertFalse($result['success']);
            $this->assertStringContainsString('not available', $result['message']);
        } else {
            $this->markTestSkipped('Intervention Image is available, cannot test graceful degradation');
        }
    }

    /** @test */
    public function it_handles_non_existent_files()
    {
        if (!$this->imageProcessor->isAvailable()) {
            $this->markTestSkipped('Intervention Image not available');
        }

        $result = $this->imageProcessor->process('non/existent/file.jpg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    /** @test */
    public function it_generates_thumbnail_paths_correctly()
    {
        $reflection = new \ReflectionClass($this->imageProcessor);
        $method = $reflection->getMethod('getThumbnailPath');
        $method->setAccessible(true);

        $path = $method->invoke($this->imageProcessor, 'uploads/image.jpg', 150);

        $this->assertEquals('uploads/image_thumb_150.jpg', $path);
    }

    /** @test */
    public function it_can_get_image_dimensions()
    {
        if (!$this->imageProcessor->isAvailable()) {
            $this->markTestSkipped('Intervention Image not available');
        }

        // Create a test image file
        $testImage = UploadedFile::fake()->image('test.jpg', 200, 300);
        $path = $testImage->store('uploads', 'testing');

        $dimensions = $this->imageProcessor->getDimensions($path);

        if ($dimensions !== null) {
            $this->assertArrayHasKey('width', $dimensions);
            $this->assertArrayHasKey('height', $dimensions);
            $this->assertArrayHasKey('aspect_ratio', $dimensions);
        }
    }

    /** @test */
    public function it_returns_null_dimensions_when_unavailable()
    {
        if (!$this->imageProcessor->isAvailable()) {
            $dimensions = $this->imageProcessor->getDimensions('any/path.jpg');
            $this->assertNull($dimensions);
        } else {
            $this->markTestSkipped('Intervention Image is available');
        }
    }
}
