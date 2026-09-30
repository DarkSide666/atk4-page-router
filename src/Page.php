<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use Atk4\Ui\ViewWithContent;
use InvalidArgumentException;
use OutOfBoundsException;

/**
 * Base class for application pages.
 */
abstract class Page extends ViewWithContent
{
    /**
     * Route parameters matched from the request path.
     *
     * @var array<string, string>
     */
    protected $routeParams = [];

    /**
     * Returns permissions accepted for this page.
     *
     * If multiple permissions are returned, access is granted when any one of
     * them is allowed by the configured access checker. An empty array means
     * that the page does not require a permission.
     *
     * @return list<string>
     */
    public static function getRequiredPermission(): array
    {
        return [];
    }

    /**
     * Returns all route parameters matched by the router.
     *
     * @return array<string, string>
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Returns a single route parameter.
     *
     * @throws OutOfBoundsException if the parameter does not exist.
     */
    public function getRouteParam(string $name): string
    {
        if (!array_key_exists($name, $this->routeParams)) {
            throw new OutOfBoundsException(sprintf('Route parameter "%s" is not defined.', $name));
        }

        return $this->routeParams[$name];
    }

    /**
     * Called when the page becomes part of the render tree.
     */
    protected function init(): void
    {
        parent::init();

        if (!is_array($this->routeParams)) {
            throw new InvalidArgumentException('Page routeParams must be an array.');
        }

        $this->build();
    }

    abstract protected function build(): void;
}
