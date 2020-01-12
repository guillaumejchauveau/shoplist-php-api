<?php


namespace GECU\Rest\Kernel;


use Symfony\Component\HttpFoundation\Request;

class RestRequest extends Request
{
    protected $route;
    protected $resourcePath;
    protected $resourceClassName;
    protected $resourceAction;
    protected $resourceRequestContentClassName;
    protected $webroot;

    public function __construct(
      array $query = [],
      array $request = [],
      array $attributes = [],
      array $cookies = [],
      array $files = [],
      array $server = [],
      $content = null,
      $routes = [],
      string $webroot = ''
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->route = null;
        $this->resourcePath = null;
        $this->resourceClassName = null;
        $this->resourceAction = null;
        $this->resourceRequestContentClassName = null;
        $this->webroot = $webroot;

        $path = substr($this->getRequestUri(), strlen($this->getBasePath()));
        if (!empty($path) && $path[-1] !== '/') {
            $this->resourcePath = $path;
            foreach ($routes as $route) {
                $match = $route->match($this);
                if (is_array($match)) {
                    $this->resourceClassName = $route->getResourceClass();
                    $this->resourceAction = $route->getAction();
                    $this->resourceRequestContentClassName = $route->getRequestContentClass();
                    foreach ($match as $key => $value) {
                        $this->attributes->set($key, $value);
                    }
                    $this->route = $route;
                    break;
                }
            }
        }
    }

    protected function prepareBaseUrl()
    {
        $base = dirname($this->server->get('PHP_SELF'));
        $indexPos = strpos($base, '/' . $this->webroot);
        if ($indexPos !== false) {
            $base = substr($base, 0, $indexPos);
        }
        return $base;
    }

    /**
     * @return mixed
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return false|string
     */
    public function getResourcePath()
    {
        return $this->resourcePath;
    }

    /**
     * @return mixed
     */
    public function getResourceClassName()
    {
        return $this->resourceClassName;
    }

    /**
     * @return mixed
     */
    public function getResourceAction()
    {
        return $this->resourceAction;
    }

    /**
     * @return mixed
     */
    public function getResourceRequestContentClassName()
    {
        return $this->resourceRequestContentClassName;
    }

}
