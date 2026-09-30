<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use Atk4\PageRouter\Exception\AccessDeniedException;
use Atk4\PageRouter\Exception\RouteNotFoundException;
use Atk4\Ui\App;
use LogicException;

final class Router
{
    /** @var array<string, Route> Exact routes keyed by normalized path. */
    private $routes = [];

    /** @var list<Route> Parameterized routes in registration order. */
    private $parameterizedRoutes = [];

    /** @var AccessCheckerInterface|null */
    private $accessChecker;

    /** @var string Base URL path under which the application is mounted. */
    private $baseRoot = '/';

    public function __construct(?AccessCheckerInterface $accessChecker = null, string $baseRoot = '/')
    {
        $this->accessChecker = $accessChecker;
        $this->setBaseRoot($baseRoot);
    }

    /**
     * Set the URL path under which the application is mounted.
     *
     * For example, when the application is available at
     * http://localhost/DarkSide666/atk4-page-router/demos/, use:
     * /DarkSide666/atk4-page-router/demos
     *
     * @return $this
     */
    public function setBaseRoot(string $baseRoot): self
    {
        $this->baseRoot = $this->normalizePath($baseRoot);

        return $this;
    }

    /**
     * Return the configured application mount path.
     */
    public function getBaseRoot(): string
    {
        return $this->baseRoot;
    }

    /**
     * Register a page route.
     *
     * Exact routes are matched before parameterized routes.
     *
     * @param class-string<Page> $pageClass
     */
    public function add(string $path, string $pageClass): self
    {
        $normalizedPath = $this->normalizePath($path);
        $route = new Route($normalizedPath, $pageClass);

        if (!$route->isParameterized()) {
            if (isset($this->routes[$route->path])) {
                throw new LogicException(sprintf('Route "%s" is already registered.', $route->path));
            }

            $this->routes[$route->path] = $route;

            return $this;
        }

        foreach ($this->parameterizedRoutes as $existingRoute) {
            if ($existingRoute->path === $route->path) {
                throw new LogicException(sprintf('Route "%s" is already registered.', $route->path));
            }
        }

        $this->parameterizedRoutes[] = $route;

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
        $requestPath = $this->normalizePath($app->getRequest()->getUri()->getPath());
        $path = $this->stripBaseRoot($requestPath);

        if ($path === null) {
            throw new RouteNotFoundException($requestPath);
        }

        [$route, $routeParams] = $this->findRoute($path);

        if ($route === null) {
            throw new RouteNotFoundException($path);
        }

        $permissions = $route->pageClass::getRequiredPermission();
        if ($permissions !== []) {
            $allowed = false;

            if ($this->accessChecker !== null) {
                foreach ($permissions as $permission) {
                    if ($this->accessChecker->hasPermission($permission, $app)) {
                        $allowed = true;
                        break;
                    }
                }
            }

            if (!$allowed) {
                throw new AccessDeniedException($route->pageClass);
            }
        }

        /** @var Page $page */
        $page = $route->pageClass::addTo($app, [
            'routeParams' => $routeParams,
        ]);

        return $page;
    }

    /**
     * @return list<Route>
     */
    public function getRoutes(): array
    {
        return array_merge(array_values($this->routes), $this->parameterizedRoutes);
    }

    /**
     * @return array{0: Route|null, 1: array<string, string>}
     */
    private function findRoute(string $path): array
    {
        $route = $this->routes[$path] ?? null;
        if ($route !== null) {
            return [$route, []];
        }

        foreach ($this->parameterizedRoutes as $candidateRoute) {
            $params = $candidateRoute->match($path);
            if ($params !== null) {
                return [$candidateRoute, $params];
            }
        }

        return [null, []];
    }

    /**
     * Convert the full request path into a path relative to the configured base root.
     *
     * @return string|null null if the request is outside the configured base root
     */
    private function stripBaseRoot(string $path): ?string
    {
        if ($this->baseRoot === '/') {
            return $path;
        }

        if ($path === $this->baseRoot) {
            return '/';
        }

        $prefix = $this->baseRoot . '/';
        if (strpos($path, $prefix) === 0) {
            return $this->normalizePath(substr($path, strlen($this->baseRoot)));
        }

        return null;
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
