<?php

declare(strict_types=1);

namespace Ava\Tests\Http;

use Ava\Http\Response;
use Ava\Testing\TestCase;

final class ResponseTest extends TestCase
{
    public function testConstructorDefaultsAndExplicitValues(): void
    {
        foreach ([
            [new Response(), '', 200, []],
            [new Response('Hello World', 404, ['X-Test' => 'value']), 'Hello World', 404, ['X-Test' => 'value']],
        ] as [$response, $content, $status, $headers]) {
            $this->assertSame($content, $response->content());
            $this->assertSame($status, $response->status());
            $this->assertSame($headers, $response->headers());
        }
    }

    public function testFactoriesSetContentStatusAndHeaders(): void
    {
        $cases = [
            'redirect' => [Response::redirect('/new-location'), '', 302, ['Location' => '/new-location']],
            'permanent redirect' => [Response::redirect('/permanent', 301), '', 301, ['Location' => '/permanent']],
            'json' => [Response::json(['key' => 'value']), '{"key":"value"}', 200, ['Content-Type' => 'application/json; charset=utf-8']],
            'json error' => [Response::json(['error' => 'Not found'], 404), '{"error":"Not found"}', 404, ['Content-Type' => 'application/json; charset=utf-8']],
            'text' => [Response::text('Plain text'), 'Plain text', 200, ['Content-Type' => 'text/plain; charset=utf-8']],
            'html' => [Response::html('<p>HTML</p>'), '<p>HTML</p>', 200, ['Content-Type' => 'text/html; charset=utf-8']],
            'not found' => [Response::notFound(), 'Not Found', 404, []],
            'custom not found' => [Response::notFound('Page not found'), 'Page not found', 404, []],
        ];
        foreach ($cases as $name => [$response, $content, $status, $headers]) {
            $this->assertSame($content, $response->content(), "$name content");
            $this->assertSame($status, $response->status(), "$name status");
            $this->assertSame($headers, $response->headers(), "$name headers");
        }
    }

    public function testHeadersAreCaseInsensitiveAndCollapseDuplicates(): void
    {
        $response = new Response('', 200, ['X-Test' => 'first', 'x-test' => 'second']);
        $this->assertSame(['X-Test' => 'second'], $response->headers());
        $this->assertSame('second', $response->header('x-TEST'));
        $this->assertNull($response->header('missing'));

        foreach ([
            $response->withHeader('x-test', 'third'),
            $response->withHeaders(['x-test' => 'third']),
        ] as $modified) {
            $this->assertNotSame($response, $modified);
            $this->assertSame(['X-Test' => 'third'], $modified->headers());
        }
        $this->assertSame(['X-Test' => 'second'], $response->headers());
    }

    public function testModifiersCanBeChainedWithoutChangingOriginal(): void
    {
        $original = new Response('old');
        $cases = [
            'withContent' => [['new'], 'new', 200, []],
            'withStatus' => [[404], 'old', 404, []],
            'withHeader' => [['X-Custom', 'value'], 'old', 200, ['X-Custom' => 'value']],
            'withHeaders' => [[['X-One' => '1', 'X-Two' => '2']], 'old', 200, ['X-One' => '1', 'X-Two' => '2']],
        ];
        foreach ($cases as $method => [$arguments, $content, $status, $headers]) {
            $modified = $original->$method(...$arguments);
            $this->assertNotSame($original, $modified, $method);
            $this->assertSame($content, $modified->content(), $method);
            $this->assertSame($status, $modified->status(), $method);
            $this->assertSame($headers, $modified->headers(), $method);
            $this->assertSame('old', $original->content(), $method);
            $this->assertSame(200, $original->status(), $method);
            $this->assertSame([], $original->headers(), $method);
        }
        $chained = $original->withContent('Hello')->withStatus(201)->withHeader('X-Custom', 'value');
        $this->assertSame('Hello', $chained->content());
        $this->assertSame(201, $chained->status());
        $this->assertSame('value', $chained->header('X-Custom'));
    }

    public function testJsonEncodesNestedArraysWithoutEscapingSlashes(): void
    {
        $data = ['items' => [1, 2, 3], 'user' => ['name' => 'John'], 'url' => 'https://example.com/path'];
        $content = Response::json($data)->content();
        $this->assertSame($data, json_decode($content, true, flags: JSON_THROW_ON_ERROR));
        $this->assertStringContains('https://example.com/path', $content);
    }
}
