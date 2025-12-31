<?php

declare(strict_types=1);

namespace Ava\Tests\Support;

use Ava\Support\Arr;
use Ava\Testing\TestCase;

/**
 * Tests for the Arr utility class.
 */
final class ArrTest extends TestCase
{
    public function testGetReturnsTopLevelValue(): void
    {
        $arr = ['name' => 'Ava'];
        $this->assertEquals('Ava', Arr::get($arr, 'name'));
    }

    public function testGetReturnsNestedValue(): void
    {
        $arr = ['site' => ['name' => 'Ava CMS']];
        $this->assertEquals('Ava CMS', Arr::get($arr, 'site.name'));
    }

    public function testGetReturnsDeeplyNestedValue(): void
    {
        $arr = ['a' => ['b' => ['c' => 'deep']]];
        $this->assertEquals('deep', Arr::get($arr, 'a.b.c'));
    }

    public function testGetReturnsDefaultForMissingKey(): void
    {
        $arr = ['name' => 'Ava'];
        $this->assertEquals('default', Arr::get($arr, 'missing', 'default'));
    }

    public function testGetReturnsDefaultForMissingNestedKey(): void
    {
        $arr = ['site' => []];
        $this->assertNull(Arr::get($arr, 'site.name'));
    }

    public function testGetReturnsNullAsDefault(): void
    {
        $arr = [];
        $this->assertNull(Arr::get($arr, 'missing'));
    }
}
