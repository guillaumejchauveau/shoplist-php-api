<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Route;

class RestRequestFactory
{
    /**
     * @var Route[]
     */
    protected $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function createRestRequest(
      array $query = [],
      array $request = [],
      array $attributes = [],
      array $cookies = [],
      array $files = [],
      array $server = [],
      $content = null
    ): RestRequest {
        return new RestRequest(
          $query,
          $request,
          $attributes,
          $cookies,
          $files,
          $server,
          $content,
          $this->routes
        );
    }
}
