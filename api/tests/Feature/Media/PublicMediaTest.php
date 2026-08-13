<?php

namespace Tests\Feature\Media;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

final class PublicMediaTest extends TestCase
{
    private bool $createdStorageLink = false;

    private ?string $publicMediaPath = null;

    protected function setUp(): void
    {
        parent::setUp();

        $publicStorageLink = public_path('storage');

        if (is_link($publicStorageLink)) {
            $this->assertSame(storage_path('app/public'), realpath($publicStorageLink));

            return;
        }

        if (file_exists($publicStorageLink)) {
            throw new RuntimeException('public/storage exists but is not Laravel\'s standard symbolic link.');
        }

        Artisan::call('storage:link');
        $this->createdStorageLink = true;
    }

    protected function tearDown(): void
    {
        if ($this->publicMediaPath !== null) {
            @unlink($this->publicMediaPath);
        }

        if ($this->createdStorageLink) {
            @unlink(public_path('storage'));
        }

        parent::tearDown();
    }

    public function test_public_disk_configuration_and_fake_storage_are_available(): void
    {
        $this->assertSame(storage_path('app/public'), config('filesystems.disks.public.root'));
        $this->assertSame('http://localhost:8000/storage', config('filesystems.disks.public.url'));

        Storage::fake('public');
        Storage::disk('public')->put('proof/public-media.txt', 'public-media-proof');

        Storage::disk('public')->assertExists('proof/public-media.txt');
    }

    public function test_standard_storage_link_serves_public_media_over_http(): void
    {
        $filename = 'public-media-http-'.Str::uuid().'.txt';
        $contents = 'public-media-http-proof';

        $this->assertTrue(Storage::disk('public')->put($filename, $contents));
        $this->publicMediaPath = Storage::disk('public')->path($filename);

        $this->assertTrue(is_link(public_path('storage')));
        $this->assertSame(storage_path('app/public'), realpath(public_path('storage')));

        [$server, $port] = $this->startPublicFileServer();

        try {
            $this->assertSame($contents, $this->fetchPublicMedia($port, $filename));
        } finally {
            proc_terminate($server);
            proc_close($server);
        }
    }

    /**
     * @return array{resource, int}
     */
    private function startPublicFileServer(): array
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);

        if ($socket === false) {
            throw new RuntimeException("Unable to reserve an HTTP test port: {$errorMessage} ({$errorCode}).");
        }

        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        $port = (int) substr(strrchr($address, ':'), 1);
        $server = proc_open(
            [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', public_path()],
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
        );

        if (! is_resource($server)) {
            throw new RuntimeException('Unable to start the HTTP test server.');
        }

        fclose($pipes[0]);

        return [$server, $port];
    }

    private function fetchPublicMedia(int $port, string $filename): string
    {
        $url = "http://127.0.0.1:{$port}/storage/{$filename}";
        $context = stream_context_create(['http' => ['timeout' => 1]]);

        for ($attempt = 0; $attempt < 50; $attempt++) {
            $contents = @file_get_contents($url, false, $context);

            if ($contents !== false) {
                return $contents;
            }

            usleep(50_000);
        }

        throw new RuntimeException("Public media was not served at {$url}.");
    }
}
