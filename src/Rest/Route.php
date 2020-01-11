<?php


namespace GECU\Rest;


use Doctrine\Common\Annotations\Annotation\Target;
use GECU\Rest\Kernel\Api;
use GECU\Rest\Kernel\RestRequest;
use InvalidArgumentException;

/**
 * Represents a mapping between a URL and a resource.
 * @package GECU\Rest
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *   @Attribute("method", type="string", required=true),
 *   @Attribute("path", type="string", required=true),
 *   @Attribute("requestContentClass", type="string"),
 *   @Attribute("status", type="integer"),
 * })
 */
class Route
{
    public const PATH_PARAM_BEGIN = '{';
    public const PATH_PARAM_END = '}';
    /**
     * @var string
     */
    protected $resourceClassName;
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
    protected $actionName;
    /**
     * @var string|null
     */
    protected $requestContentClassName;
    /**
     * @var int|null
     */
    protected $status;
    /**
     * @var string[]
     */
    protected $query;

    public function __construct(array $args)
    {
        $this->method = $args['method'];
        $this->setPath($args['path']);
        $this->requestContentClassName = $args['requestContentClass'] ?? null;
        $this->status = $args['status'] ?? null;
        $this->query = $args['query'] ?? [];
    }

    protected function setPath(string $path): void
    {
        $this->pathParts = [];
        if (empty($path) || $path[0] !== '/') {
            throw new InvalidArgumentException('Invalid path');
        }
        $path = substr($path, 1);
        foreach (explode('/', $path) as $part) {
            if ($part[0] === self::PATH_PARAM_BEGIN && $part[-1] === self::PATH_PARAM_END) {
                $this->pathParts[] = [
                  trim($part, self::PATH_PARAM_BEGIN . self::PATH_PARAM_END)
                ];
            } else {
                $this->pathParts[] = $part;
            }
        }
    }

    /**
     * @param array $args
     * @param string $resourceClassName
     * @param string|null $actionName
     * @return static
     */
    public static function fromArray(
      array $args,
      string $resourceClassName,
      ?string $actionName = null
    ): self {
        $route = new static($args);
        $route->setResourceClassName($resourceClassName);
        $route->setActionName($actionName);
        return $route;
    }

    public function getResourceClassName(): string
    {
        return $this->resourceClassName;
    }

    public function setResourceClassName(string $class): void
    {
        $this->resourceClassName = $class;
    }

    /**
     * @return string|null
     */
    public function getActionName(): ?string
    {
        return $this->actionName;
    }

    public function setActionName(?string $action): void
    {
        $this->actionName = $action;
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
    public function getRequestContentClassName(): ?string
    {
        return $this->requestContentClassName;
    }

    public function match(RestRequest $request): ?array
    {
        if ($request->getMethod() !== $this->getMethod()) {
            return null;
        }

        $requestPath = $request->getResourcePath();
        if ($requestPath[0] === '/') {
            $requestPath = substr($requestPath, 1);
        }
        $params = [];
        foreach ($this->pathParts as $pathPart) {
            if ($requestPath === false || empty($requestPath)) {
                return null;
            }
            $requestPathPartEnd = strpos($requestPath, '/');
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
        foreach ($this->query as $queryArgName) {
            if ($request->query->has($queryArgName)) {
                $params[$queryArgName] = $request->query->get($queryArgName);
            } else {
                $params[$queryArgName] = null;
            }
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
            $path .= '/';
            if (is_array($part)) {
                $path .= self::PATH_PARAM_BEGIN . $part . self::PATH_PARAM_END;
            } else {
                $path .= $part;
            }
        }
        return $path;
    }
}
