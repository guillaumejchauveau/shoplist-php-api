<?php


namespace GECU\Rest;


use Doctrine\Common\Annotations\Annotation\Target;
use GECU\Rest\Helper\FactoryHelper;
use GECU\Rest\Kernel\RestRequest;
use InvalidArgumentException;

/**
 * Represents a mapping between a URL and a resource.
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @Attributes({
 *   @Attribute("method", type="string", required=true),
 *   @Attribute("path", type="string", required=true),
 *   @Attribute("requestContentFactory", type="mixed"),
 *   @Attribute("status", type="integer"),
 *   @Attribute("query", type="array<string>")
 * })
 */
class Route
{
    private const PATH_PARAM_BEGIN = '{';
    private const PATH_PARAM_END = '}';
    /**
     * The class name of the resource (MyResource::class).
     * @var string
     */
    protected $resourceClassName;
    /**
     * The HTTP method.
     * @var string
     */
    protected $method;
    /**
     * An array representing the route's URL relative to the API. Route
     * parameters are represented as an array containing the parameter's name as
     * only element.
     * @var array
     */
    protected $pathParts;
    /**
     * The name of the method in the resource's class. If null, the resource
     * instance itself will be the result of the request.
     * @var string|null
     */
    protected $actionName;
    /**
     * A pseudo callable used to create a data structure for the request's body.
     * @var mixed|null
     * @see FactoryHelper
     */
    protected $requestContentFactory;
    /**
     * The return type of the request content factory.
     * @var string|null
     */
    protected $requestContentType;
    /**
     * The HTTP status of the response if no errors are encountered.
     * @var int|null
     */
    protected $status;
    /**
     * A list of query parameter names.
     * @var string[]
     */
    protected $query;

    /**
     * This constructor should not be used directly. Use {@see Route::fromArray()}
     * instead.
     * @param array $args
     */
    public function __construct(array $args)
    {
        $this->method = $args['method'];
        $this->setPath($args['path']);
        $this->setRequestContentFactory($args['requestContentFactory'] ?? null);
        $this->status = $args['status'] ?? null;
        $this->query = $args['query'] ?? [];
    }

    /**
     * Sets the path of the route by computing its parts.
     * @param string $path
     */
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
     * Creates a route from an array of route properties.
     * @param array $args
     * @param string $resourceClassName
     * @param string|null $actionName
     * @return Route
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

    /**
     * @return string
     */
    public function getResourceClassName(): string
    {
        return $this->resourceClassName;
    }

    /**
     * @param string $class
     */
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

    /**
     * @param string|null $action
     */
    public function setActionName(?string $action): void
    {
        $this->actionName = $action;
    }

    /**
     * @return int|null
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @return mixed|null A pseudo callable
     * @see FactoryHelper
     */
    public function getRequestContentFactory()
    {
        return $this->requestContentFactory;
    }

    /**
     * Sets the request content factory and updates the request content type
     * accordingly.
     * @param $contentFactory mixed A pseudo callable
     * @see FactoryHelper
     */
    protected function setRequestContentFactory($contentFactory): void
    {
        $this->requestContentFactory = $contentFactory;
        $this->requestContentType = null;
        if ($contentFactory === null) {
            return;
        }
        $returnType = FactoryHelper::getFactoryReturnType($contentFactory);
        if ($returnType === null) {
            throw new InvalidArgumentException('Invalid content request factory');
        }
        $this->requestContentType = $returnType;
    }

    /**
     * @return string|null
     */
    public function getRequestContentType(): ?string
    {
        return $this->requestContentType;
    }

    /**
     * Determines if the route matches a given request. If so, an associative
     * array of the route's parameters and query parameters is returned.
     * @param RestRequest $request
     * @return array|null
     */
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
            // Part is a route parameter.
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

    /**
     * @return string A string representation of the route's path
     */
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
