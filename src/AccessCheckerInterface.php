<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use Atk4\Ui\App;

interface AccessCheckerInterface
{
    /**
     * @param class-string<Page> $pageClass
     */
    public function canAccess(string $pageClass, App $app): bool;
}
