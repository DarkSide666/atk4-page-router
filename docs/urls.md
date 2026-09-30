# URLs and deployment below a web root

`PageRouter` intentionally does not define a `url()` method. ATK4's `$app->url()` and `$app->jsUrl()` remain the only URL-generation APIs used by the application.

```php
$app->url('/users');
$app->url('/users/42/edit');
$app->jsUrl('/users/42/edit');
```

This keeps routing and URL generation separate and allows existing ATK4 applications to keep using their normal URL helpers.

## Extensionless URLs

For front-controller routing, configure the ATK4 application to build URLs without `index.php` or `.php` suffixes:

```php
final class App extends \Atk4\Ui\App
{
    protected string $urlBuildingIndexPage = '';
    protected string $urlBuildingExt = '';
}
```

Then:

```php
$app->url('/users');
```

can produce `/users` instead of `/users.php`, assuming the web server rewrites the request to the application's front controller.

## Apache

A simple `.htaccess` for a demo or front-controller application:

```apache
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

RewriteRule ^ index.php [QSA,L]
```

## nginx

A typical equivalent configuration is:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Application mounted below the server root

Suppose the application is available at:

```text
http://localhost/DarkSide666/atk4-page-router/demos/
```

Configure the router with:

```php
$router = new Router(null, '/DarkSide666/atk4-page-router/demos');
```

The router uses this value only to normalize incoming request paths. It does not modify URLs produced by `$app->url()` or `$app->jsUrl()`.

The application itself should configure URL generation according to its ATK4 deployment environment. The demo contains a small `DemoApp` override to prepend its runtime mount directory while keeping the package itself independent of that concern.
