<?php

declare(strict_types=1);

namespace Ava\Tests\Content;

use Ava\Content\Indexer;
use Ava\Testing\TestCase;

final class IndexerLockTest extends TestCase
{
    public function testReadLockExcludesRebuildWriter(): void
    {
        $reader = new Indexer($this->app);
        $reader->acquireReadLock();

        $lockPath = $this->app->configPath('storage') . '/cache/.rebuild.lock';
        $writer = fopen($lockPath, 'c+b');
        if ($writer === false) {
            $this->fail('Unable to open rebuild lock in test.');
        }

        $this->assertFalse(flock($writer, LOCK_EX | LOCK_NB));

        unset($reader);
        $this->assertTrue(flock($writer, LOCK_EX | LOCK_NB));

        flock($writer, LOCK_UN);
        fclose($writer);
    }
}
