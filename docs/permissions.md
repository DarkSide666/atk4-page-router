# Permissions

Pages can declare one or more permissions using `Page::getRequiredPermission()`.

```php
final class UsersPage extends Page
{
    public static function getRequiredPermission(): array
    {
        return ['users.view'];
    }
}
```

If multiple permissions are returned, they use **OR semantics**: the page is accessible when the current user has at least one of them.

```php
final class UserEditPage extends Page
{
    public static function getRequiredPermission(): array
    {
        return ['users.edit', 'users.admin'];
    }
}
```

This means the user needs either `users.edit` or `users.admin`.

An empty array makes the page public:

```php
public static function getRequiredPermission(): array
{
    return [];
}
```

## Access checker

Provide an implementation of `AccessCheckerInterface` to the router:

```php
use Atk4\PageRouter\AccessCheckerInterface;
use Atk4\Ui\App;

final class AccessChecker implements AccessCheckerInterface
{
    public function hasPermission(string $permission, App $app): bool
    {
        return $this->currentUserHas($permission);
    }
}

$router = new Router(new AccessChecker());
```

The checker is responsible for deciding whether the current user has a particular permission. It can use the application's session, authentication layer, model, or another authorization service.

If a page requires permissions but no checker is configured, access is denied.

When all declared permissions are denied, `dispatch()` throws `AccessDeniedException`.

## Keep authorization below the page level too

Page permissions are useful for controlling access to routes, but they should not be the only authorization boundary for sensitive operations. Model actions, mutations, and other protected business operations should enforce their own authorization as appropriate.
