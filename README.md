# ATK4 Page Router

Small application-level page router for [ATK4 UI](https://www.atk4.org/ui).

The package provides a thin routing layer that maps request paths to ATK4 `Page` classes, supports named path parameters, and optionally checks page permissions.

## Requirements

- PHP `>= 7.4 < 8.4`
- `ext-json`
- `atk4/ui` `dev-develop`

## Installation

```bash
composer require atk4/page-router
```

## Basic usage

```php
use Atk4\PageRouter\Page;
use Atk4\PageRouter\Router;
use Atk4\Ui\App;
use Atk4\Ui\Header;

final class HomePage extends Page
{
    protected function build(): void
    {
        Header::addTo($this, ['Home', 'size' => 1]);
    }
}

final class UserEditPage extends Page
{
    protected function build(): void
    {
        $id = $this->getRouteParam('id');

        Header::addTo($this, ['Edit user #' . $id, 'size' => 1]);
    }
}

$app = new App();

$router = new Router();
$router
    ->add('/', HomePage::class)
    ->add('/users/{id}/edit', UserEditPage::class);

$router->dispatch($app);
$app->run();
```

`Router` matches the request URI path. Query parameters are not used for application routing, so ATK4 callback parameters continue to be handled by ATK4 UI.

## URL generation

The router does not provide a second URL-generation API. Use ATK4's existing `$app->url()` and `$app->jsUrl()` throughout the application.

```php
$app->url('/users/42/edit');
$app->jsUrl('/users/42/edit');
```

See [docs/urls.md](docs/urls.md) for deployment below a web-server root and rewrite configuration.

## Documentation

- [Routing](docs/routing.md)
- [Permissions](docs/permissions.md)
- [URLs and base root](docs/urls.md)

## Tests

Install development dependencies and run:

```bash
composer install
vendor/bin/phpunit
```

The test suite follows ATK4's PHPUnit conventions and uses `Atk4\Core\Phpunit\TestCase`.

## License

MIT
