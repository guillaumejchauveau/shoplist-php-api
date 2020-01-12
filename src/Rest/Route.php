<?php


namespace GECU\Rest;


use GECU\Rest\Kernel\RestRequest;
use InvalidArgumentException;

class Route
{
    public const PATH_DELIMITER = '/';
    public const PATH_PARAM_BEGIN = '{';
    public const PATH_PARAM_END = '}';
    /**
     * @var string
     */
    protected $resourceClass;
    /**
     * @var string
     */
    protected $method;
    /**
     * @var array
     */
    protected $pathParts;
    /**
     * @var string|null
     */
    protected $action;
    /**
     * @var string|null
     */
    protected $requestContentClass;
    /**
     * @var int|null
     */
    protected $status;

    public function __construct(array $args)
    {
        $this->method = $args['method'];
        $this->setPath($args['path']);
        $this->requestContentClass = $args['requestContentClass'] ?? null;
        $this->status = $args['status'] ?? null;
    }

    protected function setPath(string $path): void
    {
        $this->pathParts = [];
        if (empty($path) || $path[0] !== self::PATH_DELIMITER) {
            throw new InvalidArgumentException('Invalid path');
        }
        $path = substr($path, 1);
        foreach (explode(self::PATH_DELIMITER, $path) as $part) {
            if ($part[0] === self::PATH_PARAM_BEGIN && $part[-1] === self::PATH_PARAM_END) {
                $this->pathParts[] = [
                  trim($part, self::PATH_PARAM_BEGIN . self::PATH_PARAM_END)
                ];
            } else {
                $this->pathParts[] = $part;
            }
        }
    }

    public static function fromArray(
      array $args,
      string $resourceClass,
      ?string $action = null
    ): self {
        $route = new self($args);
        $route->setResourceClass($resourceClass);
        $route->setAction($action);
        return $route;
    }

    public function getResourceClass(): string
    {
        return $this->resourceClass;
    }

    public function setResourceClass(string $class): void
    {
        $this->resourceClass = $class;
    }

    /**
     * @return string
     */
    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(?string $action): void
    {
        $this->action = $action;
    }

    /**
     * @return int
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getRequestContentClass(): ?string
    {
        return $this->requestContentClass;
    }

    public function match(RestRequest $request): ?array
    {
        if ($request->getMethod() !== $this->getMethod()) {
            return null;
        }

        $requestPath = $request->getResourcePath();
        $params = [];
        foreach ($this->pathParts as $pathPart) {
            if (empty($requestPath)) {
                return null;
            }
            $requestPathPartEnd = strpos($requestPath, self::PATH_DELIMITER);
            if ($requestPathPartEnd !== false) {
                $requestPathPart = substr($requestPath, 0, $requestPathPartEnd);
                $requestPath = substr_replace($requestPath, '', 0, $requestPathPartEnd + 1);
            } else {
                $requestPathPart = $requestPath;
                $requestPath = '';
            }
            if (is_array($pathPart)) {
                $params[$pathPart[0]] = $requestPathPart;
            } elseif ($requestPathPart !== $pathPart) {
                return null;
            }
        }
        if (!empty($requestPath)) {
            return null;
        }
        return $params;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    public function __toString()
    {
        return sprintf('%s %s', $this->getMethod(), $this->getPath());
    }

    public function getPath(): string
    {
        $path = '';
        foreach ($this->pathParts as $part) {
            $path .= self::PATH_DELIMITER;
            if (is_array($part)) {
                $path .= self::PATH_PARAM_BEGIN . $part . self::PATH_PARAM_END;
            } else {
                $path .= $part;
            }
        }
        return $path;
    }
}
