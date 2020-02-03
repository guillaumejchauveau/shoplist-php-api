<?php


namespace GECU\Rest\Kernel;


use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use GECU\Rest\Helper\ArgumentResolver;
use GECU\Rest\Helper\FactoryHelper;
use GECU\Rest\Helper\ManufacturableInterface;
use GECU\Rest\Helper\RequestContentValueResolver;
use GECU\Rest\Helper\ServiceValueResolver;
use GECU\Rest\ResourceFactory;
use GECU\Rest\RoutableInterface;
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

/**
 * Class responsible of the life cycle of a REST API.
 */
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
     * The list of all the API's routes.
     * @var Route[]
     */
    protected $routes;
    /**
     * An associative array of the resource class names with their corresponding
     * factory.
     * @var mixed[]
     */
    protected $resourceFactories;
    /**
     * @var Reader
     */
    protected $annotationReader;

    /**
     * Api constructor.
     * @param string[] $resources The class names of the resources
     * @param string $webroot The path of the API entry point's directory on the
     *  server
     * @param ContainerInterface|null $container A service container for the
     *  factories and resource actions
     * @param Reader|null $annotationReader A custom annotation reader
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
     * Sets the API's resources by registering their routes and factories.
     * @param string[] $resources A list of class names
     */
    protected function setResources(iterable $resources): void
    {
        $this->routes = [];
        $this->resourceFactories = [];
        foreach ($resources as $resourceClassName) {
            try {
                $resourceClass = new ReflectionClass($resourceClassName);
            } catch (ReflectionException $e) {
                throw new InvalidArgumentException('Invalid resource class name');
            }
            $resourceRoutes = $this->getResourceClassRoutes($resourceClass);
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
            $this->resourceFactories[$resourceClassName] = $this->getResourceClassFactory(
              $resourceClass
            );
        }
    }

    /**
     * Computes the routes of a given resource, either using
     * {@see RoutableInterface} if available or by reading the annotations.
     * @param ReflectionClass $resourceClass
     * @return iterable An iterable containing {@see Route} instances or arrays
     *  describing a route
     */
    protected function getResourceClassRoutes(ReflectionClass $resourceClass): iterable
    {
        if ($resourceClass->implementsInterface(RoutableInterface::class)) {
            /** @var RoutableInterface $resourceClassName */
            $resourceClassName = $resourceClass->getName();
            return $resourceClassName::getRoutes();
        }
        $resourceClassName = $resourceClass->getName();
        $routes = [];
        // On-class route annotation.
        /** @var Route|null $resourceRoute */
        $resourceRoute = $this->annotationReader->getClassAnnotation(
          $resourceClass,
          Route::class
        );
        if ($resourceRoute !== null) {
            $resourceRoute->setResourceClassName($resourceClassName);
            $routes[] = $resourceRoute;
        }
        foreach ($resourceClass->getMethods() as $method) {
            $resourceRoute = $this->annotationReader->getMethodAnnotation(
              $method,
              Route::class
            );
            if ($resourceRoute !== null) {
                $resourceRoute->setResourceClassName($resourceClassName);
                $resourceRoute->setActionName($method->getName());
                $routes[] = $resourceRoute;
            }
        }
        return $routes;
    }

    /**
     * Computes the factory of a given resource, either using
     * {@see ManufacturableInterface} if available or by reading annotations. If
     * no resource factory can be found, the resource's constructor will be
     * used.
     * @param ReflectionClass $resourceClass
     * @return mixed The factory as a pseudo callable
     * @see FactoryHelper
     */
    protected function getResourceClassFactory(ReflectionClass $resourceClass)
    {
        // Resource has ManufacturableTrait.
        if ($resourceClass->implementsInterface(ManufacturableInterface::class)) {
            /** @var ManufacturableInterface $resourceClassName */
            $resourceClassName = $resourceClass->getName();
            return $resourceClassName::getFactory() ?? [$resourceClassName, '__construct'];
        }
        $resourceClassName = $resourceClass->getName();
        // On-class resource factory annotation.
        /** @var ResourceFactory|null $resourceFactory */
        $resourceFactory = $this->annotationReader->getClassAnnotation(
          $resourceClass,
          ResourceFactory::class
        );
        if ($resourceFactory !== null) {
            if ($resourceFactory->value === null) {
                throw new InvalidArgumentException(
                  'Resource factory annotation on class should have a value'
                );
            }
            return $resourceFactory->value;
        }
        foreach ($resourceClass->getMethods() as $method) {
            $resourceFactory = $this->annotationReader->getMethodAnnotation(
              $method,
              ResourceFactory::class
            );
            if ($resourceFactory !== null) {
                if ($resourceFactory->value !== null) {
                    throw new InvalidArgumentException(
                      'Resource factory annotation on method should not have a value'
                    );
                }
                return [$resourceClassName, $method->getName()];
            }
        }
        return [$resourceClassName, '__construct'];
    }

    /**
     * Processes the current request.
     */
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

    /**
     * Generates a response to a given request.
     * @param RestRequest $request
     * @return Response
     */
    protected function handleRequest(RestRequest $request): Response
    {
        // Handles CORS pre-flight request.
        if ($request->isMethod(Request::METHOD_OPTIONS)) {
            return new RestResponse(
              null, Response::HTTP_NO_CONTENT, [
                    'Access-Control-Allow-Methods' => '*',
                    'Access-Control-Allow-Headers' => '*'
                  ]
            );
        }
        if ($request->getRoute() === null) {
            throw new NotFoundHttpException('No resources corresponding');
        }
        $factory = $this->resourceFactories[$request->getRoute()->getResourceClassName()];
        try {
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

    /**
     * Generates a response to a given error.
     * @param Throwable $throwable
     * @return Response
     */
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
