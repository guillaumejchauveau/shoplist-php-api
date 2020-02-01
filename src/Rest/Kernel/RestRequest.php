<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * A request holding a resource route. The corresponding route's parameters are
 * set as request attributes.
 * @see Request::$attributes
 */
class RestRequest extends Request
{
    /**
     * @var Route|null
     */
    protected $route;
    /**
     * @var string
     */
    protected $webroot;

    /**
     * @inheritDoc
     * @param Route[] $routes The routes of all the resources
     * @param string $webroot The path of the API entry point's directory on the
     *  server
     */
    public function __construct(
      array $query = [],
      array $request = [],
      array $attributes = [],
      array $cookies = [],
      array $files = [],
      array $server = [],
      $content = null,
      iterable $routes = [],
      string $webroot = ''
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->webroot = $webroot;
        $this->route = null;
        if (!empty($this->getPathInfo()) && $this->getPathInfo()[-1] !== '/') {
            foreach ($routes as $route) {
                $match = $route->match($this);
                if (is_array($match)) {
                    $this->route = $route;
                    foreach ($match as $key => $value) {
                        $this->attributes->set($key, $value);
                    }
                    break;
                }
            }
        }
    }

    /**
     * @return string The path of the resource in the API
     */
    public function getResourcePath(): string
    {
        return $this->getPathInfo();
    }

    /**
     * @return Route|null The route matching the request, if any
     */
    public function getRoute(): ?Route
    {
        return $this->route;
    }

    /**
     * @inheritDoc
     */
    protected function prepareBaseUrl(): string
    {
        $base = dirname($this->server->get('PHP_SELF'));
        $indexPos = strpos($base, '/' . $this->webroot);
        if ($indexPos !== false) {
            $base = substr($base, 0, $indexPos);
        }
        return $base;
    }
}
