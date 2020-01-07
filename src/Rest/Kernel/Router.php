<?php


namespace GECU\Rest\Kernel;


use GECU\Rest\RestResource;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class Router implements EventSubscriberInterface
{
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var RestResource[]
     */
    protected $resources;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
          KernelEvents::REQUEST => 'onRequest'
        ];
    }

    public function __construct(string $basePath, array $resources)
    {
        if (empty($basePath)) {
            throw new InvalidArgumentException('Invalid base path');
        }
        $this->basePath = $basePath;
        $this->resources = $resources;
    }

    protected function getRequestPath(Request $request): string
    {
        $path = $request->getRequestUri();
        if (empty($this->basePath) || strpos($path, $this->basePath) !== 0) {
            throw new RuntimeException('Invalid base path');
        }
        return substr($path, strlen($this->basePath));
    }

    protected function matchResource(Request $request): ?RestResource
    {
        foreach ($this->resources as $resource) {
            if ($resource->match($request->getMethod(), $this->getRequestPath($request))) {
                return $resource;
            }
        }
        return null;
    }

    public function onRequest(RequestEvent $event)
    {
        $request = $event->getRequest();
        if (!$request->attributes->has('_controller')) {
            $resource = $this->matchResource($request);
            if ($resource === null) {
                throw new NotFoundHttpException("No resource associated with request");
            }

            $request->attributes->set(
              '_controller',
              $resource->getController()
            );

            foreach ($resource->getResourceArguments($this->getRequestPath($request)) as $key => $value) {
                $request->attributes->set($key, $value);
            }

            if ($resource->getRequestBodyConverter() !== null) {
                $request->attributes->set(
                  'requestData',
                  $resource->getRequestBodyConverter()($request->getContent())
                );
            }
        }
    }
}
