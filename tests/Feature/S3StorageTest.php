<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class S3StorageTest extends TestCase
{
    public function test_s3_upload_read_private_access_and_delete(): void
    {
        $disk = Storage::disk('s3');
        $filename = 'test_jagasewa_connection.txt';
        $content = 'jagasewa-s3-test-content';

        // 1. Upload to S3
        $disk->put($filename, $content);
        $this->assertTrue($disk->exists($filename), 'Failed asserting that file exists in S3.');
        $this->assertEquals($content, $disk->get($filename), 'Failed asserting that S3 content matches.');

        // 2. Test that direct unauthenticated access is denied (private bucket check)
        $endpoint = rtrim(config('filesystems.disks.s3.endpoint'), '/');
        $bucket = config('filesystems.disks.s3.bucket');
        $directUrl = "{$endpoint}/{$bucket}/{$filename}";

        $response = Http::get($directUrl);
        $this->assertTrue(
            in_array($response->status(), [401, 403]),
            "Expected direct URL to be private (HTTP 401 or 403), but got HTTP {$response->status()}"
        );

        // 3. Delete and verify cleanup
        $disk->delete($filename);
        $this->assertFalse($disk->exists($filename), 'Failed asserting that file was deleted from S3.');
    }
}
