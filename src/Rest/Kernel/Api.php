<?php


namespace GECU\Rest\Kernel;


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
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Api
{
    public const REQUEST_ATTRIBUTE_PATH = 'resourcePath';
    public const REQUEST_ATTRIBUTE_CLASS = 'resourceClass';
    public const REQUEST_ATTRIBUTE_ACTION = 'resourceAction';
    public const REQUEST_ATTRIBUTE_REQUEST_CONTENT_CLASS = 'resourceRequestContentClass';
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var ArgumentResolverInterface
     */
    protected $argumentResolver;
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var Route[]
     */
    protected $resourceRoutes;
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
     * @param string $basePath
     * @param string[] $resources
     * @param ContainerInterface|null $container
     * @param Reader|null $annotationReader
     * @throws AnnotationException
     */
    public function __construct(
      string $basePath,
      array $resources,
      ?ContainerInterface $container = null,
      ?Reader $annotationReader = null
    ) {
        AnnotationRegistry::registerFile(dirname(__DIR__) . '/RestAnnotations.php');
        if (empty($basePath)) {
            throw new InvalidArgumentException('Invalid base path');
        }
        $this->basePath = $basePath;

        $container = $container ?? new Container();
        $this->container = $container;

        $argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
        $argumentValueResolvers[] = new ServiceArgumentValueResolver($this->container);
        $argumentValueResolvers[] = new RequestContentAsResourceArgumentValueResolver();
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);

        $this->annotationReader = $annotationReader ?? new AnnotationReader();

        $this->setResources($resources);
    }

    /**
     * @param string[] $resources
     */
    protected function setResources(array $resources)
    {
        $this->resourceRoutes = [];
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
                    $resourceRoute->setResourceClass($resourceClassName);
                    $this->resourceRoutes[] = $resourceRoute;
                }
                foreach ($resourceClass->getMethods() as $method) {
                    $factory = $this->annotationReader->getMethodAnnotation(
                      $method,
                      ResourceFactory::class
                    );
                    if ($factory !== null) {
                        if ($resourceFactory !== null) {
                            throw new InvalidArgumentException(
                              'Resource must have only one constructor'
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
                        $resourceRoute->setResourceClass($resourceClassName);
                        $resourceRoute->setAction($method->getName());
                        $this->resourceRoutes[] = $resourceRoute;
                    }
                }

                if ($resourceFactory === null) {
                    throw new InvalidArgumentException('Resource must have one constructor');
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

    public function run(): void
    {
        $request = Request::createFromGlobals();
        try {
            $route = $this->prepareRequest($request);
            $response = $this->handleRequest($request);
            if ($route->getStatus() !== null) {
                $response->setStatusCode($route->getStatus());
            }
            $response->send();
        } catch (Throwable $e) {
            $this->handleError($e)->send();
        }
    }

    protected function prepareRequest(Request $request): Route
    {
        $path = substr($request->getRequestUri(), strlen($this->basePath));
        if (!empty($path) && $path[-1] !== Route::PATH_DELIMITER) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE_PATH, $path);
            foreach ($this->resourceRoutes as $route) {
                $match = $route->match($request);
                if (is_array($match)) {
                    $request->attributes->set(
                      self::REQUEST_ATTRIBUTE_CLASS,
                      $route->getResourceClass()
                    );
                    $request->attributes->set(self::REQUEST_ATTRIBUTE_ACTION, $route->getAction());
                    $request->attributes->set(
                      self::REQUEST_ATTRIBUTE_REQUEST_CONTENT_CLASS,
                      $route->getRequestContentClass()
                    );
                    foreach ($match as $key => $value) {
                        $request->attributes->set($key, $value);
                    }

                    return $route;
                }
            }
        }

        throw new NotFoundHttpException('No resources corresponding');
    }

    protected function handleRequest(Request $request): Response
    {
        $resourceClass = $request->attributes->get(self::REQUEST_ATTRIBUTE_CLASS);
        $resourceAction = $request->attributes->get(self::REQUEST_ATTRIBUTE_ACTION);
        $resourceFactory = $this->resourceFactories[$resourceClass];
        $resourceFactoryArgs = $this->argumentResolver->getArguments(
          $request,
          $resourceFactory
        );
        try {
            $resource = $resourceFactory(...$resourceFactoryArgs);

            if ($resourceAction === null) {
                $response = $resource;
            } else {
                $action = [$resource, $resourceAction];
                $arguments = $this->argumentResolver->getArguments($request, $action);
                $response = $action(...$arguments);
            }

            return new RestResponse($response);
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
