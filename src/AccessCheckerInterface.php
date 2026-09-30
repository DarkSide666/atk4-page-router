<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use Atk4\Ui\App;

interface AccessCheckerInterface
{
    /**
     * Check whether the current user has the given permission.
     */
    public function hasPermission(string $permission, App $app): bool;
}
