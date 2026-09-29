<?php

declare(strict_types=1);

namespace Atk4\PageRouter\Exception;

use RuntimeException;

final class AccessDeniedException extends RuntimeException
{
    /**
     * @param class-string $pageClass
     */
    public function __construct(string $pageClass)
    {
        parent::__construct(sprintf('Access denied for page %s.', $pageClass));
    }
}
