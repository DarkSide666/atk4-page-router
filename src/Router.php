<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use Atk4\PageRouter\Exception\AccessDeniedException;
use Atk4\PageRouter\Exception\RouteNotFoundException;
use Atk4\Ui\App;
use LogicException;

final class Router
{
    /** @var array<string, Route> */
    private $routes = [];

    /** @var AccessCheckerInterface|null */
    private $accessChecker;

    public function __construct(AccessCheckerInterface $accessChecker = null)
    {
        $this->accessChecker = $accessChecker;
    }

    /**
     * Register a page route.
     *
     * @param class-string<Page> $pageClass
     */
    public function add(string $path, string $pageClass): self
    {
        $route = new Route($this->normalizePath($path), $pageClass);

        if (isset($this->routes[$route->path])) {
            throw new LogicException(sprintf('Route "%s" is already registered.', $route->path));
        }

        $this->routes[$route->path] = $route;

        return $this;
    }

    /**
     * Route the current request and add the selected page to the ATK app.
     *
     * Routing is intentionally based on the request path only. Query parameters,
     * including ATK callback parameters, are left untouched for ATK UI to process.
     */
    public function dispatch(App $app): Page
    {
        $path = $this->normalizePath($app->getRequest()->getUri()->getPath());
        $route = isset($this->routes[$path]) ? $this->routes[$path] : null;

        if ($route === null) {
            throw new RouteNotFoundException($path);
        }

        if ($this->accessChecker !== null && !$this->accessChecker->canAccess($route->pageClass, $app)) {
            throw new AccessDeniedException($route->pageClass);
        }

        /** @var Page $page */
        $page = $route->pageClass::addTo($app);

        return $page;
    }

    /**
     * @return list<Route>
     */
    public function getRoutes(): array
    {
        return array_values($this->routes);
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        return $path === '/' ? '/' : rtrim($path, '/');
    }
}
