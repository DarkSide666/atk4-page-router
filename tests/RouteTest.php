<?php

declare(strict_types=1);

namespace Atk4\PageRouter\Tests;

use Atk4\Core\Phpunit\TestCase;
use Atk4\PageRouter\Page;
use Atk4\PageRouter\Route;
use InvalidArgumentException;

final class RouteTestPage extends Page
{
    protected function build(): void
    {
    }
}

class RouteTest extends TestCase
{
    public function testMatch(): void
    {
        foreach (self::provideMatchCases() as $case) {
            [$routePath, $requestPath, $expected] = $case;
            $route = new Route($routePath, RouteTestPage::class);

            self::assertSame($expected, $route->match($requestPath));
        }
    }

    /**
     * @return iterable<array{string, string, array<string, string>|null}>
     */
    public static function provideMatchCases(): iterable
    {
        yield ['/users', '/users', []];
        yield ['/users', '/users/', []];
        yield ['/users/{id}', '/users/123', ['id' => '123']];
        yield ['/users/{id}/edit', '/users/123/edit', ['id' => '123']];
        yield ['/companies/{companyId}/users/{userId}', '/companies/10/users/42', [
            'companyId' => '10',
            'userId' => '42',
        ]];
        yield ['/users/{id}', '/users/hello%20world', ['id' => 'hello world']];
        yield ['/users/{id}', '/users/123/edit', null];
    }

    public function testParameterized(): void
    {
        self::assertTrue((new Route('/users/{id}', RouteTestPage::class))->isParameterized());
        self::assertFalse((new Route('/users', RouteTestPage::class))->isParameterized());
    }

    public function testDuplicateParameterException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate parameter "id"');

        new Route('/users/{id}/orders/{id}', RouteTestPage::class);
    }

    public function testInvalidPageClassException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must extend');

        $class = \stdClass::class;
        new Route('/users', $class);
    }
}
