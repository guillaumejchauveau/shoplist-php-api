<?php


namespace GECU\Rest;


class RestResource
{
    /**
     * @var string
     */
    protected $method;
    protected $pathParts;
    /**
     * @var Callable
     */
    protected $requestBodyConverter;
    /**
     * @var Callable
     */
    protected $controller;

    public function __construct(
      string $method,
      string $path,
      Callable $controller,
      ?Callable $requestBodyConverter = null
    ) {
        $this->method = $method;
        $this->pathParts = static::formatPath($path);
        $this->controller = $controller;
        $this->requestBodyConverter = $requestBodyConverter;
    }

    protected static function formatPath(string $path): array
    {
        $pathParts = [];
        foreach (explode('/', $path) as $part) {
            if ($part[0] === '{' && $part[-1] === '}') {
                $pathParts[] = [trim($part, '{}')];
            } else {
                $pathParts[] = $part;
            }
        }
        return $pathParts;
    }

    /**
     * @return Callable|null
     */
    public function getRequestBodyConverter(): ?Callable
    {
        return $this->requestBodyConverter;
    }

    /**
     * @return Callable
     */
    public function getController(): Callable
    {
        return $this->controller;
    }

    public function match(string $method, $path): bool
    {
        $otherParts = static::formatPath($path);
        if ($this->method !== $method || count($otherParts) !== count($this->pathParts)) {
            return false;
        }
        $i = 0;
        foreach ($this->pathParts as $part) {
            if (!is_array($part) && $part !== $otherParts[$i]) {
                return false;
            }
            $i++;
        }
        return true;
    }

    public function getResourceArguments(string $path): array
    {
        $inputParts = static::formatPath($path);
        $args = [];
        $i = 0;
        foreach ($this->pathParts as $part) {
            if (is_array($part)) {
                $args[$part[0]] = $inputParts[$i];
            }
            $i++;
        }
        return $args;
    }
}
