<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Route;
use Symfony\Component\HttpFoundation\Request;

class RestRequest extends Request
{
    protected $route;
    protected $resourcePath;
    protected $resourceClassName;
    protected $resourceAction;
    protected $resourceRequestContentClassName;

    public function __construct(
      array $query = [],
      array $request = [],
      array $attributes = [],
      array $cookies = [],
      array $files = [],
      array $server = [],
      $content = null,
      $routes = []
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->route = null;
        $this->resourcePath = null;
        $this->resourceClassName = null;
        $this->resourceAction = null;
        $this->resourceRequestContentClassName = null;

        $path = substr(
          $this->getRequestUri(),
          strlen('/UK/Web_Applications/Project/API/')
        );  // TODO
        if (!empty($path) && $path[-1] !== Route::PATH_DELIMITER) {
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
