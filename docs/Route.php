<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use InvalidArgumentException;

final class Route
{
    /** @var string */
    public $path;

    /** @var class-string<Page> */
    public $pageClass;

    /**
     * @param class-string<Page> $pageClass
     */
    public function __construct(string $path, string $pageClass)
    {
        if ($path === '' || $path[0] !== '/') {
            throw new InvalidArgumentException('Route path must start with /.');
        }

        if (!is_a($pageClass, Page::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Route page class %s must extend %s.',
                $pageClass,
                Page::class,
            ));
        }

        $this->path = $path;
        $this->pageClass = $pageClass;
    }
}
