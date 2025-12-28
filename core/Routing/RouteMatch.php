<?php

declare(strict_types=1);

namespace Ava\Routing;

use Ava\Application;
use Ava\Content\Item;
use Ava\Content\Query;
use Ava\Http\Request;
use Ava\Http\Response;

/**
 * Route Match
 *
 * Represents a matched route with all context needed for rendering.
 */
final class RouteMatch
{
    private string $type;
    private ?Item $contentItem;
    private ?Query $query;
    private ?array $taxonomy;
    private string $template;
    private ?string $redirectUrl;
    private int $redirectCode;
    private array $params;
    private ?Response $response;

    public function __construct(
        string $type,
        ?Item $contentItem = null,
        ?Query $query = null,
        ?array $taxonomy = null,
        string $template = 'index.php',
        ?string $redirectUrl = null,
        int $redirectCode = 302,
        array $params = [],
        ?Response $response = null
    ) {
        $this->type = $type;
        $this->contentItem = $contentItem;
        $this->query = $query;
        $this->taxonomy = $taxonomy;
        $this->template = $template;
        $this->redirectUrl = $redirectUrl;
        $this->redirectCode = $redirectCode;
        $this->params = $params;
        $this->response = $response;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getContentItem(): ?Item
    {
        return $this->contentItem;
    }

    public function getQuery(): ?Query
    {
        return $this->query;
    }

    public function getTaxonomy(): ?array
    {
        return $this->taxonomy;
    }

    public function getTemplate(): string
    {
        return $this->template;
    }

    public function isRedirect(): bool
    {
        return $this->redirectUrl !== null;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getRedirectCode(): int
    {
        return $this->redirectCode;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function hasResponse(): bool
    {
        return $this->response !== null;
    }
}
