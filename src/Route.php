<?php

declare(strict_types=1);

namespace Atk4\PageRouter;

use InvalidArgumentException;

final class Route
{
    /** @var string */
    public $path;

    /** @var class-string<Page> */
    public $pageClass;

    /** @var string */
    private $pattern;

    /** @var list<string> */
    private $parameterNames;

    public function __construct(string $path, string $pageClass)
    {
        if ($path === '' || $path[0] !== '/') {
            throw new InvalidArgumentException('Route path must start with /.');
        }

        if (!is_a($pageClass, Page::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Route page class %s must extend %s.',
                $pageClass,
                Page::class,
            ));
        }

        $this->path = $path;
        /* @var class-string<Page> $pageClass */
        $this->pageClass = $pageClass;

        [$this->pattern, $this->parameterNames] = $this->compilePath($path);
    }

    /**
     * Match a request path against this route.
     *
     * @return array<string, string>|null
     */
    public function match(string $path): ?array
    {
        if (preg_match($this->pattern, $path, $matches) !== 1) {
            return null;
        }

        $params = [];
        foreach ($this->parameterNames as $parameterName) {
            $params[$parameterName] = rawurldecode($matches[$parameterName]);
        }

        return $params;
    }

    /**
     * Returns true if this route contains one or more path parameters.
     */
    public function isParameterized(): bool
    {
        return $this->parameterNames !== [];
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function compilePath(string $path): array
    {
        $parts = preg_split('/(\{[A-Za-z_][A-Za-z0-9_]*\})/', $path, -1, \PREG_SPLIT_DELIM_CAPTURE);
        if ($parts === false) {
            throw new InvalidArgumentException(sprintf('Unable to parse route path "%s".', $path));
        }

        $pattern = '';
        $parameterNames = [];
        $usedParameters = [];

        foreach ($parts as $part) {
            if ($part !== '' && $part[0] === '{' && substr($part, -1) === '}') {
                $parameterName = substr($part, 1, -1);

                if (isset($usedParameters[$parameterName])) {
                    throw new InvalidArgumentException(sprintf(
                        'Route "%s" contains duplicate parameter "%s".',
                        $path,
                        $parameterName,
                    ));
                }

                $usedParameters[$parameterName] = true;
                $parameterNames[] = $parameterName;
                $pattern .= '(?P<' . $parameterName . '>[^/]+)';
            } else {
                $pattern .= preg_quote($part, '~');
            }
        }

        return ['~^' . $pattern . '/?$~', $parameterNames];
    }
}
