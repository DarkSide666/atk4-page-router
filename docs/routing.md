# Routing

`Router` maps application URL paths to classes extending `Atk4\PageRouter\Page`.

## Registering routes

Routes are registered with `add()`:

```php
$router
    ->add('/', HomePage::class)
    ->add('/users', UsersPage::class)
    ->add('/users/{id}/edit', UserEditPage::class);
```

The route path must start with `/`.

## Exact and parameterized routes

Exact routes are checked first. Parameterized routes are checked in registration order after exact routes.

For example:

```php
$router->add('/users/{id}', UserPage::class);
$router->add('/users/new', NewUserPage::class);
```

`/users/new` resolves to `NewUserPage`, while `/users/42` resolves to `UserPage` with `id = 42`.

## Route parameters

Named parameters use `{name}` syntax:

```php
$router->add('/companies/{companyId}/users/{userId}', UserPage::class);
```

The selected page receives the parameters through `Page`:

```php
$companyId = $this->getRouteParam('companyId');
$userId = $this->getRouteParam('userId');
```

You can also retrieve all parameters:

```php
$params = $this->getRouteParams();
```

Parameter values are URL-decoded before they are exposed to the page.

`getRouteParam()` throws `OutOfBoundsException` when the requested parameter does not exist.

## Trailing slashes

Routes accept an optional trailing slash. `/users` and `/users/` match the same route.

## Not found

If no registered route matches the request, `dispatch()` throws `RouteNotFoundException`.

## Base root

When the application is mounted below the web-server root, configure the router with that mount path:

```php
$router = new Router(null, '/my-app');
```

For a site available at:

```text
http://localhost/DarkSide666/atk4-page-router/demos/
```

the base root is:

```text
/DarkSide666/atk4-page-router/demos
```

A request to `/DarkSide666/atk4-page-router/demos/users/42/edit` is then matched internally as `/users/42/edit`.

Requests outside the configured base root are not matched.

## Callbacks

The router only inspects the request path. It deliberately ignores query parameters such as ATK4 callback parameters.

A normal ATK4 callback request therefore follows the same page route that created the callback:

```text
/users
  -> UsersPage
  -> ATK4 reconstructs the UI/callback
  -> callback is executed by ATK4
```

Do not add special callback handling to `Router`.
