<?php

declare(strict_types=1);

namespace Ava\Tests\Http;

use Ava\Http\RedirectTarget;
use Ava\Testing\TestCase;

final class RedirectTargetTest extends TestCase
{
    public function testAllowsLocalPathsAndExplicitHttpUrls(): void
    {
        foreach ([
            '/new-page',
            '/articles?page=2#results',
            'https://example.com/new-page',
            'http://127.0.0.1:8080/path',
        ] as $target) {
            $this->assertEquals($target, RedirectTarget::sanitize($target));
        }
    }

    public function testRejectsAmbiguousOrUnsafeTargets(): void
    {
        foreach ([
            '//evil.example/path',
            '/\\evil.example/path',
            '\\evil.example/path',
            'https:\\evil.example/path',
            'javascript:alert(1)',
            'data:text/html,unsafe',
            'mailto:user@example.com',
            'relative/path',
            'https:///missing-host',
            "https://example.com/\r\nX-Test: injected",
        ] as $target) {
            $this->assertNull(RedirectTarget::sanitize($target), 'Unsafe redirect was accepted: ' . $target);
        }
    }
}
