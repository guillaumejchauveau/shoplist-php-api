<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Route;
use Symfony\Component\HttpFoundation\Request;

class RestRequest extends Request
{
    protected $route;
    protected $webroot;

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

    /**
     * @return string
     */
    public function getResourcePath(): string
    {
        return $this->getPathInfo();
    }

    /**
     * @return Route|null
     */
    public function getRoute(): ?Route
    {
        return $this->route;
    }
}
