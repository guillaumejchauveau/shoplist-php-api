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
     * @param string[] $resources
     * @param string $webroot
     * @param ContainerInterface|null $container
     * @param Reader|null $annotationReader
     * @throws AnnotationException
     */
    public function __construct(
      array $resources,
      string $webroot = '',
      ?ContainerInterface $container = null,
      ?Reader $annotationReader = null
    ) {
        AnnotationRegistry::registerFile(dirname(__DIR__) . '/RestAnnotations.php');

        $container = $container ?? new Container();
        $this->container = $container;

        $argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
        $argumentValueResolvers[] = new ServiceArgumentValueResolver($this->container);
        $argumentValueResolvers[] = new RequestContentAsResourceArgumentValueResolver();
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);

        $this->annotationReader = $annotationReader ?? new AnnotationReader();

        $this->setResources($resources);
        $requestFactory = new RestRequestFactory($this->resourceRoutes, $webroot);
        RestRequest::setFactory([$requestFactory, 'createRestRequest']);
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
                        $resourceRoute->setResourceClass($resourceClassName);
                        $resourceRoute->setAction($method->getName());
                        $this->resourceRoutes[] = $resourceRoute;
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
        if ($request->getRoute() === null) {
            throw new NotFoundHttpException('No resources corresponding');
        }
        $resourceFactory = $this->resourceFactories[$request->getResourceClassName()];
        $resourceFactoryArgs = $this->argumentResolver->getArguments(
          $request,
          $resourceFactory
        );
        try {
            $resource = $resourceFactory(...$resourceFactoryArgs);

            if ($request->getResourceAction() === null) {
                $response = $resource;
            } else {
                $action = [$resource, $request->getResourceAction()];
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
