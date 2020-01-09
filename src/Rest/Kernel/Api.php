<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\ResourceRoute;
use InvalidArgumentException;
use RuntimeException;
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
     * @var ResourceRoute[]
     */
    protected $resourceRoutes;

    /**
     * Api constructor.
     * @param string $basePath
     * @param string[] $resources
     * @param ContainerInterface|null $container
     */
    public function __construct(
      string $basePath,
      array $resources,
      ?ContainerInterface $container = null
    ) {
        if (empty($basePath)) {
            throw new InvalidArgumentException('Invalid base path');
        }
        $this->basePath = $basePath;

        $this->resourceRoutes = [];
        foreach ($resources as $resource) {
            foreach ($resource::getRoutes() as $route) {
                if ($route instanceof ResourceRoute) {
                    $this->resourceRoutes[] = $route;
                } elseif (is_array($route)) {
                    $this->resourceRoutes[] = ResourceRoute::fromArray($resource, $route);
                } else {
                    throw new RuntimeException('Invalid route');
                }
            }
        }

        $container = $container ?? new Container();
        $this->container = $container;

        $argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
        $argumentValueResolvers[] = new ServiceArgumentValueResolver($this->container);
        $argumentValueResolvers[] = new RequestContentAsResourceArgumentValueResolver();
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);
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

    protected function prepareRequest(Request $request): ResourceRoute
    {
        $path = substr($request->getRequestUri(), strlen($this->basePath));
        if (!empty($path) && $path[-1] !== ResourceRoute::PATH_DELIMITER) {
            $request->attributes->set(self::REQUEST_ATTRIBUTE_PATH, $path);
            foreach ($this->resourceRoutes as $route) {
                $match = $route->match($request);
                if (is_array($match)) {
                    $request->attributes->set(self::REQUEST_ATTRIBUTE_CLASS, $route->getResourceClass());
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
        $resourceConstructor = call_user_func([$resourceClass, 'getResourceConstructor']);
        $resourceConstructorArgs = $this->argumentResolver->getArguments($request, $resourceConstructor);
        try {
            $resource = $resourceConstructor(...$resourceConstructorArgs);

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
            return new RestResponse($throwable, $throwable->getStatusCode(), $throwable->getHeaders());
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
