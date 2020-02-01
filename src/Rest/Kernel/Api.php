<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\Helper\ArgumentResolver;
use GECU\Rest\Helper\FactoryHelper;
use GECU\Rest\Helper\RequestContentValueResolver;
use GECU\Rest\Helper\ServiceValueResolver;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use GECU\Rest\ResourceFactory;
use GECU\Rest\Route;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
     * @var ArgumentResolver
     */
    protected $argumentResolver;
    /**
     * @var Route[]
     */
    protected $routes;
    /**
     * @var callable[]
     */
    protected $resourceFactories;
    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * Api constructor.
     * @param string[] $resources
     * @param string $webroot
     * @param ContainerInterface|null $container
     * @param Reader|null $annotationReader
     * @throws AnnotationException
     */
    public function __construct(
      iterable $resources,
      string $webroot,
      ?ContainerInterface $container = null,
      ?Reader $annotationReader = null
    ) {
        AnnotationRegistry::registerFile(dirname(__DIR__) . '/RestAnnotations.php');
        $this->container = $container ?? new Container();

        $serviceArgumentValueResolver = new ServiceValueResolver($this->container);
        $argumentValueResolvers = [
          $serviceArgumentValueResolver,
          new RequestContentValueResolver(
            [$serviceArgumentValueResolver]
          ),
        ];
        array_push(
          $argumentValueResolvers,
          ...ArgumentResolver::getDefaultArgumentValueResolvers()
        );
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);

        $this->annotationReader = $annotationReader ?? new AnnotationReader();

        $this->setResources($resources);
        $requestFactory = new RestRequestFactory($this->routes, $webroot);
        RestRequest::setFactory([$requestFactory, 'create']);
    }

    /**
     * @param string[] $resources
     */
    protected function setResourcesAnno(array $resources): void
    {
        $this->routes = [];
        $this->resourceFactories = [];
        foreach ($resources as $resourceClassName) {
            try {
                $resourceClass = new ReflectionClass($resourceClassName);
                $resourceFactory = null;

                $resourceRoute = $this->annotationReader->getClassAnnotation(
                  $resourceClass,
                  Route::class
                );
                if ($resourceRoute !== null) {
                    $resourceRoute->setResourceClassName($resourceClassName);
                    $this->routes[] = $resourceRoute;
                }
                foreach ($resourceClass->getMethods() as $method) {
                    $factory = $this->annotationReader->getMethodAnnotation(
                      $method,
                      ResourceFactory::class
                    );
                    if ($factory !== null) {
                        if ($resourceFactory !== null) {
                            throw new InvalidArgumentException(
                              'Resource must have only one factory'
                            );
                        }
                        $resourceFactory = $method->getName();
                        continue;
                    }
                    $resourceRoute = $this->annotationReader->getMethodAnnotation(
                      $method,
                      Route::class
                    );
                    if ($resourceRoute !== null) {
                        $resourceRoute->setResourceClassName($resourceClassName);
                        $resourceRoute->setActionName($method->getName());
                        $this->routes[] = $resourceRoute;
                    }
                }

                if ($resourceFactory === null) {
                    throw new InvalidArgumentException('Resource must have one factory');
                }
                $this->resourceFactories[$resourceClassName] = [
                  $resourceClassName,
                  $resourceFactory
                ];
            } catch (ReflectionException $e) {
                throw new InvalidArgumentException('Resource must be a valid class name');
            }
        }
    }

    /**
     * @param string[] $resources
     */
    protected function setResources(iterable $resources): void
    {
        $this->routes = [];
        foreach ($resources as $resourceClassName) {
            $resourceRoutes = call_user_func([$resourceClassName, 'getRoutes']);
            foreach ($resourceRoutes as $route) {
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
        try {
            $factory = call_user_func(
              [$request->getRoute()->getResourceClassName(), 'getResourceFactory']
            );
            $resource = FactoryHelper::invokeFactory($factory, $request, $this->argumentResolver);
        } catch (InvalidArgumentException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }
        $actionName = $request->getRoute()->getActionName();
        if ($actionName === null) {
            $response = $resource;
        } else {
            /** @var callable $action */
            $action = [$resource, $actionName];
            $arguments = $this->argumentResolver->getArguments($request, $action);
            try {
                $response = $action(...$arguments);
            } catch (InvalidArgumentException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        $response = new RestResponse($response);
        if ($request->getRoute()->getStatus() !== null) {
            $response->setStatusCode($request->getRoute()->getStatus());
        }
        return $response;
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
}
