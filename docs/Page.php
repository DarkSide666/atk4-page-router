<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use Atk4\Ui\ViewWithContent;

/**
 * Base class for application pages.
 */
abstract class Page extends ViewWithContent
{
    /**
     * Returns the permission required to access this page.
     *
     * A null value means that the page has no permission requirement.
     */
    public static function getRequiredPermission(): ?string
    {
        return null;
    }

    protected function init(): void
    {
        parent::init();

        $this->build();
    }

    abstract protected function build(): void;
}
