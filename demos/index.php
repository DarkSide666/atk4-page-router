<?php

declare(strict_types=1);

namespace Atk4\PageRouterDemo;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Atk4\PageRouter\AccessCheckerInterface;
use Atk4\PageRouter\Page;
use Atk4\PageRouter\Router;
use Atk4\Ui\App;
use Atk4\Ui\Button;
use Atk4\Ui\Header;
use Atk4\Ui\Label;
use Atk4\Ui\Layout;
use Atk4\Ui\Message;

/**
 * Demo App configured for extensionless URLs handled by the page router.
 */
final class DemoApp extends App
{
    /** @var Router */
    public $router;

    protected string $urlBuildingIndexPage = '';
    protected string $urlBuildingExt = '';

    public function url($page = [], array $extraRequestUrlArgs = []): string
    {
        $url = parent::url($page, $extraRequestUrlArgs);
        $baseRoot = $this->getDemoBaseRoot();

        if ($baseRoot === '/' || $url === $baseRoot || strpos($url, $baseRoot . '/') === 0) {
            return $url;
        }

        return rtrim($baseRoot, '/') . '/' . ltrim($url, '/');
    }

    private function getDemoBaseRoot(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $baseRoot = dirname(str_replace('\\', '/', $scriptName));

        return $baseRoot === '.' ? '/' : rtrim($baseRoot, '/');
    }
}

/**
 * Very small role-based permission checker for demonstration purposes.
 *
 * Try ?role=viewer, ?role=editor or ?role=admin.
 */
final class DemoAccessChecker implements AccessCheckerInterface
{
    public function hasPermission(string $permission, App $app): bool
    {
        $role = $app->tryGetRequestQueryParam('role') ?? 'viewer';

        $permissions = [
            'viewer' => ['users.view'],
            'editor' => ['users.view', 'users.edit'],
            'admin' => ['users.view', 'users.admin'],
        ];

        return in_array($permission, $permissions[$role] ?? [], true);
    }
}

final class HomePage extends Page
{
    protected function build(): void
    {
        Header::addTo($this, ['Page Router demo', 'size' => 1]);

        Header::addTo($this, [
            'This page is public. Try the routes below and change the role in the URL.',
        ]);

        Button::addTo($this, ['Open users'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/users'))
        );

        Button::addTo($this, ['Open user #42 as editor'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/users/42/edit?role=editor'))
        );

        Button::addTo($this, ['Open user #42 as viewer (403)'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/users/42/edit?role=viewer'))
        );

        $callbackButton = Button::addTo($this, ['Test ATK callback']);
        $callbackButton->on('click', static function ($button) {
            return $button->text('Callback worked: ' . date('H:i:s'));
        });

        Message::addTo($this, [
            'type' => 'info',
            'content' => 'The last button is a server-side ATK callback. The page router still routes the request by path; ATK handles the callback parameters.',
        ]);

        Label::addTo($this, ['Roles: viewer, editor, admin.']);
    }
}

final class UsersPage extends Page
{
    public static function getRequiredPermission(): array
    {
        return ['users.view'];
    }

    protected function build(): void
    {
        Header::addTo($this, ['Users', 'size' => 1]);
        Header::addTo($this, ['You have the users.view permission.']);

        Button::addTo($this, ['Edit user #42 as editor'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/users/42/edit?role=editor'))
        );

        Button::addTo($this, ['Try user #42 as viewer'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/users/42/edit?role=viewer'))
        );

        Button::addTo($this, ['Back home'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/'))
        );
    }
}

final class UserEditPage extends Page
{
    public static function getRequiredPermission(): array
    {
        // Access is granted when either permission is present.
        return ['users.edit', 'users.admin'];
    }

    protected function build(): void
    {
        $id = $this->getRouteParam('id');

        Header::addTo($this, ['Edit user #' . $id, 'size' => 1]);
        Header::addTo($this, ['The {id} route parameter was captured from /users/' . $id . '/edit.']);

        Button::addTo($this, ['Back to users'])->on(
            'click',
            $this->getApp()->jsRedirect($this->getApp()->url('/users'))
        );
    }
}

$app = new DemoApp();
$app->title = 'ATK4 Page Router Demo';
$app->initLayout([Layout\Centered::class]);

$router = new Router(new DemoAccessChecker());
$router->setBaseRoot('/DarkSide666/atk4-page-router/demos');
$app->router = $router;
$router
    ->add('/', HomePage::class)
    ->add('/users', UsersPage::class)
    ->add('/users/{id}/edit', UserEditPage::class);

$router->dispatch($app);
$app->run();
