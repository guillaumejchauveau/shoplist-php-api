<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Route;
use InvalidArgumentException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Api
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var ArgumentResolverInterface
     */
    protected $argumentResolver;
    /**
     * @var Route[]
     */
    protected $routes;

    /**
     * Api constructor.
     * @param string[] $resources
     * @param string $webroot
     * @param ContainerInterface|null $container
     */
    public function __construct(
      array $resources,
      string $webroot,
      ?ContainerInterface $container = null
    ) {
        $container = $container ?? new Container();
        $this->container = $container;

        $argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
        $argumentValueResolvers[] = new ServiceArgumentValueResolver($this->container);
        $argumentValueResolvers[] = new RequestContentAsResourceArgumentValueResolver();
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);

        $this->setResources($resources);
        $requestFactory = new RestRequestFactory($this->routes, $webroot);
        RestRequest::setFactory([$requestFactory, 'createRestRequest']);
    }

    /**
     * @param string[] $resources
     */
    protected function setResources(array $resources): void
    {
        $this->routes = [];
        foreach ($resources as $resourceClassName) {
            foreach ($resourceClassName::getRoutes() as $route) {
                if ($route instanceof Route) {
                    $this->routes[] = $route;
                } elseif (is_array($route)) {
                    $this->routes[] = Route::fromArray(
                      $route,
                      $resourceClassName,
                      $route['action'] ?? null
                    );
                } else {
                    throw new InvalidArgumentException('Invalid route');
                }
            }
        }
    }

    public function run(): void
    {
        $request = RestRequest::createFromGlobals();
        try {
            $response = $this->handleRequest($request);
            $response->send();
        } catch (Throwable $e) {
            $this->handleError($e)->send();
        }
    }

    protected function handleRequest(RestRequest $request): Response
    {
        if ($request->isMethod(Request::METHOD_OPTIONS)) {
            return new RestResponse(
              null, Response::HTTP_NO_CONTENT, [
                    'Access-Control-Allow-Methods' => '*'
                  ]
            );
        }
        if ($request->getRoute() === null) {
            throw new NotFoundHttpException('No resources corresponding');
        }
        /** @var callable $resourceFactory */
        $resourceFactory = call_user_func(
          [$request->getRoute()->getResourceClassName(), 'getResourceFactory']
        );
        $resourceFactoryArgs = $this->argumentResolver->getArguments(
          $request,
          $resourceFactory
        );
        try {
            $resource = $resourceFactory(...$resourceFactoryArgs);

            $actionName = $request->getRoute()->getActionName();
            if ($actionName === null) {
                $response = $resource;
            } else {
                /** @var callable $action */
                $action = [$resource, $actionName];
                $arguments = $this->argumentResolver->getArguments($request, $action);
                $response = $action(...$arguments);
            }

            $response = new RestResponse($response);
            if ($request->getRoute()->getStatus() !== null) {
                $response->setStatusCode($request->getRoute()->getStatus());
            }
            return $response;
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    protected function handleError(Throwable $throwable): Response
    {
        if ($throwable instanceof HttpExceptionInterface) {
            return new RestResponse(
              $throwable,
              $throwable->getStatusCode(),
              $throwable->getHeaders()
            );
        }
        return new RestResponse($throwable, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
