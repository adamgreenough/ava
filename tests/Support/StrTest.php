<?php

declare(strict_types=1);

namespace Ava\Tests\Support;

use Ava\Support\Str;
use Ava\Testing\TestCase;

/**
 * Tests for the Str utility class.
 */
final class StrTest extends TestCase
{
    // === slug() ===

    public function testSlugConvertsToLowercase(): void
    {
        $this->assertEquals('hello-world', Str::slug('Hello World'));
    }

    public function testSlugReplacesSpacesWithSeparator(): void
    {
        $this->assertEquals('hello-world', Str::slug('hello world'));
    }

    public function testSlugRemovesSpecialCharacters(): void
    {
        $this->assertEquals('hello-world', Str::slug('Hello! World?'));
    }

    public function testSlugHandlesMultipleSpaces(): void
    {
        $this->assertEquals('hello-world', Str::slug('hello    world'));
    }

    public function testSlugTrimsLeadingAndTrailingSeparators(): void
    {
        $this->assertEquals('hello', Str::slug('--hello--'));
    }

    public function testSlugWithCustomSeparator(): void
    {
        $this->assertEquals('hello_world', Str::slug('Hello World', '_'));
    }

    public function testSlugWithNumbers(): void
    {
        $this->assertEquals('post-123', Str::slug('Post 123'));
    }

    // === before() / after() ===

    public function testBeforeReturnsSubstringBeforeNeedle(): void
    {
        $this->assertEquals('Hello', Str::before('Hello World', ' '));
    }

    public function testBeforeReturnsFullStringIfNotFound(): void
    {
        $this->assertEquals('Hello', Str::before('Hello', ' '));
    }

    public function testBeforeReturnsFullStringForEmptySearch(): void
    {
        $this->assertEquals('Hello', Str::before('Hello', ''));
    }

    public function testAfterReturnsSubstringAfterNeedle(): void
    {
        $this->assertEquals('World', Str::after('Hello World', ' '));
    }

    public function testAfterReturnsFullStringIfNotFound(): void
    {
        $this->assertEquals('Hello', Str::after('Hello', ' '));
    }

    // === limit() / words() ===

    public function testLimitTruncatesLongStrings(): void
    {
        $this->assertEquals('Hello...', Str::limit('Hello World', 5));
    }

    public function testLimitPreservesShortStrings(): void
    {
        $this->assertEquals('Hello', Str::limit('Hello', 10));
    }

    public function testLimitWithCustomEnding(): void
    {
        $this->assertEquals('Hello--', Str::limit('Hello World', 5, '--'));
    }

    public function testWordsLimitsWordCount(): void
    {
        $result = Str::words('one two three four five', 3);
        $this->assertEquals('one two three...', $result);
    }

    public function testWordsPreservesShortText(): void
    {
        $result = Str::words('one two', 5);
        $this->assertEquals('one two', $result);
    }
}
