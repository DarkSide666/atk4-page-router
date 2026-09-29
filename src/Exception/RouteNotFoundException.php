<?php

declare(strict_types=1);

namespace Atk4\PageRouter\Exception;

use RuntimeException;

final class RouteNotFoundException extends RuntimeException
{
    public function __construct(string $path)
    {
        parent::__construct(sprintf('No route was found for path "%s".', $path));
    }
}
