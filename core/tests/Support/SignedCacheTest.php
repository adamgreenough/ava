<?php

declare(strict_types=1);

namespace Ava\Tests\Support;

use Ava\Support\SignedCache;
use Ava\Testing\TestCase;

final class SignedCacheTest extends TestCase
{
    private string $directory;

    public function setUp(): void
    {
        $this->directory = AVA_ROOT . '/storage/tmp/test-signed-cache-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0700, true);
    }

    public function tearDown(): void
    {
        foreach (new \DirectoryIterator($this->directory) as $file) {
            if (!$file->isDot()) {
                unlink($file->getPathname());
            }
        }
        rmdir($this->directory);
    }

    public function testRoundTripPreservesNestedValuesAndSigningKey(): void
    {
        $data = ['items' => [['title' => 'Café', 'enabled' => true, 'count' => 2]], 'missing' => null];
        $first = $this->directory . '/first.bin';
        SignedCache::write($first, $data, false);
        $key = file_get_contents($this->directory . '/.cache_key');
        SignedCache::write($this->directory . '/second.bin', ['other'], false);
        $this->assertEquals($data, SignedCache::read($first));
        $this->assertEquals($key, file_get_contents($this->directory . '/.cache_key'));
    }

    public function testTamperingAndMissingKeysFailClosedWithoutCreatingKeys(): void
    {
        $file = $this->directory . '/data.bin';
        $this->assertEquals([], SignedCache::read($file));
        $this->assertFalse(file_exists($this->directory . '/.cache_key'));
        SignedCache::write($file, ['secret'], false);
        file_put_contents($file, file_get_contents($file) . 'tampered');
        $this->assertEquals([], SignedCache::read($file));
        unlink($this->directory . '/.cache_key');
        $this->assertEquals([], SignedCache::read($file));
        $this->assertFalse(file_exists($this->directory . '/.cache_key'));
    }

    public function testSignedNonArrayMalformedAndUnknownPayloadsAreRejected(): void
    {
        $file = $this->directory . '/data.bin';
        SignedCache::write($file, [], false);
        $key = file_get_contents($this->directory . '/.cache_key');
        foreach (['SZ:' . serialize('text'), 'SZ:' . serialize(new \stdClass()), 'SZ:broken', 'XX:' . serialize([])] as $payload) {
            file_put_contents($file, hash_hmac('sha256', $payload, $key, true) . $payload);
            $this->assertEquals([], SignedCache::read($file));
        }
    }

    public function testIgbinaryUsesTheSameValidationBoundary(): void
    {
        if (!function_exists('igbinary_serialize')) {
            $this->markSkipped('igbinary is unavailable');
        }
        $file = $this->directory . '/data.bin';
        SignedCache::write($file, ['format' => 'igbinary']);
        $this->assertEquals(['format' => 'igbinary'], SignedCache::read($file));
        $key = file_get_contents($this->directory . '/.cache_key');
        $payload = 'IG:' . igbinary_serialize('not an array');
        file_put_contents($file, hash_hmac('sha256', $payload, $key, true) . $payload);
        $this->assertEquals([], SignedCache::read($file));
    }
}
