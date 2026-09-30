<?php

declare(strict_types=1);

namespace Atk4\PageRouter\Tests;

use Atk4\Core\Phpunit\TestCase;
use Atk4\PageRouter\AccessCheckerInterface;
use Atk4\PageRouter\Exception\AccessDeniedException;
use Atk4\PageRouter\Exception\RouteNotFoundException;
use Atk4\PageRouter\Page;
use Atk4\PageRouter\Router;
use Atk4\Ui\App;
use Atk4\Ui\Layout;
use Nyholm\Psr7\Factory\Psr17Factory;

final class RouterPublicPage extends Page
{
    protected function build(): void {}
}

final class RouterExactPage extends Page
{
    protected function build(): void {}
}

final class RouterUserPage extends Page
{
    protected function build(): void {}

    public static function getRequiredPermission(): array
    {
        return ['users.view', 'users.admin'];
    }
}

final class RouterChecker implements AccessCheckerInterface
{
    /** @var array<string, bool> */
    public $permissions = [];

    /** @var list<string> */
    public $checkedPermissions = [];

    public function hasPermission(string $permission, App $app): bool
    {
        $this->checkedPermissions[] = $permission;

        return $this->permissions[$permission] ?? false;
    }
}

class RouterTest extends TestCase
{
    public function testExactRoute(): void
    {
        $router = new Router();
        $router->add('/users', RouterPublicPage::class);

        $page = $router->dispatch($this->createApp('/users'));

        self::assertInstanceOf(RouterPublicPage::class, $page);
        self::assertSame([], $page->getRouteParams());
    }

    public function testParameterizedRoute(): void
    {
        $router = new Router();
        $router->add('/users/{id}/edit', RouterPublicPage::class);

        $page = $router->dispatch($this->createApp('/users/123/edit'));

        self::assertInstanceOf(RouterPublicPage::class, $page);
        self::assertSame(['id' => '123'], $page->getRouteParams());
        self::assertSame('123', $page->getRouteParam('id'));
    }

    public function testExactRouteTakesPrecedence(): void
    {
        $router = new Router();
        $router->add('/users/{id}', RouterPublicPage::class);
        $router->add('/users/123', RouterExactPage::class);

        $page = $router->dispatch($this->createApp('/users/123'));

        self::assertInstanceOf(RouterExactPage::class, $page);
        self::assertSame([], $page->getRouteParams());
    }

    public function testBaseRoot(): void
    {
        $router = new Router(null, '/DarkSide666/atk4-page-router/demos');
        $router->add('/users/{id}/edit', RouterPublicPage::class);

        $page = $router->dispatch($this->createApp('/DarkSide666/atk4-page-router/demos/users/42/edit'));

        self::assertSame(['id' => '42'], $page->getRouteParams());
    }

    public function testRequestOutsideBaseRootIsNotMatched(): void
    {
        $router = new Router(null, '/demos');
        $router->add('/', RouterPublicPage::class);

        $this->expectException(RouteNotFoundException::class);

        $router->dispatch($this->createApp('/other'));
    }

    public function testQueryParametersDoNotAffectRouting(): void
    {
        $router = new Router();
        $router->add('/users/{id}/edit', RouterPublicPage::class);

        $page = $router->dispatch($this->createApp('/users/42/edit?__atk_json=1&__atk_cbtarget=test'));

        self::assertSame(['id' => '42'], $page->getRouteParams());
    }

    public function testAnyRequiredPermissionAllowsAccess(): void
    {
        $checker = new RouterChecker();
        $checker->permissions = ['users.admin' => true];

        $router = new Router($checker);
        $router->add('/users', RouterUserPage::class);

        $page = $router->dispatch($this->createApp('/users'));

        self::assertInstanceOf(RouterUserPage::class, $page);
        self::assertSame(['users.view', 'users.admin'], $checker->checkedPermissions);
    }

    public function testMissingRequiredPermissionsDenyAccess(): void
    {
        $checker = new RouterChecker();
        $router = new Router($checker);
        $router->add('/users', RouterUserPage::class);

        $this->expectException(AccessDeniedException::class);

        $router->dispatch($this->createApp('/users'));
    }

    public function testNoCheckerWithRequiredPermissionDeniesAccess(): void
    {
        $router = new Router();
        $router->add('/users', RouterUserPage::class);

        $this->expectException(AccessDeniedException::class);

        $router->dispatch($this->createApp('/users'));
    }

    public function testPublicPageDoesNotNeedChecker(): void
    {
        $checker = new RouterChecker();
        $router = new Router($checker);
        $router->add('/users', RouterPublicPage::class);

        $page = $router->dispatch($this->createApp('/users'));

        self::assertInstanceOf(RouterPublicPage::class, $page);
        self::assertSame([], $checker->checkedPermissions);
    }

    private function createApp(string $uri): App
    {
        $request = (new Psr17Factory())->createServerRequest('GET', $uri);
        $app = new App([
            'catchExceptions' => false,
            'alwaysRun' => false,
            'request' => $request,
        ]);
        // Keep router tests lightweight. App::initLayout() also initializes
        // ATK's JS/CSS includes, which is unnecessary for routing tests.
        $app->layout = new Layout();
        $app->layout->setApp($app);

        return $app;
    }
}
