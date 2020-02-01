<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @see Request::setFactory()
 */
class RestRequestFactory
{
    /**
     * @var Route[]
     */
    protected $routes;
    /**
     * @var string
     */
    protected $webroot;

    /**
     * RestRequestFactory constructor.
     * @param Route[] $routes The routes of all the resources
     * @param string $webroot The path of the API entry point's directory on the
     *  server
     */
    public function __construct(iterable $routes, string $webroot)
    {
        $this->routes = $routes;
        $this->webroot = $webroot;
    }

    /**
     * @param array $query
     * @param array $request
     * @param array $attributes
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     * @return RestRequest
     * @see Request
     */
    public function create(
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
          $this->routes,
          $this->webroot
        );
    }
}
