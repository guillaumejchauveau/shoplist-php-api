<?php


namespace GECU\Rest\Kernel;


use Exception;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class Api
{
    /**
     * @var HttpKernel
     */
    protected $kernel;
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(
      string $basePath,
      array $resources,
      ?ContainerInterface $container = null,
      ?EventDispatcherInterface $eventDispatcher = null
    ) {
        $container = $container ?: new ContainerBuilder();
        $eventDispatcher = $eventDispatcher ?: new EventDispatcher();
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher;

        $this->eventDispatcher->addSubscriber(new Router($basePath, $resources));
        $this->eventDispatcher->addSubscriber(new ErrorListener([new ErrorController(), 'handle']));
        $this->eventDispatcher->addListener(
          KernelEvents::VIEW,
          function (ViewEvent $event) {
              $event->setResponse(new RestResponse($event->getControllerResult()));
          }
        );

        $argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
        $argumentValueResolvers[] = new ServiceArgumentValueResolver($this->container);
        $this->kernel = new HttpKernel(
          $this->eventDispatcher,
          new ControllerResolver(),
          new RequestStack(),
          new ArgumentResolver(null, $argumentValueResolvers)
        );
    }

    public function run(?Request $request = null)
    {
        $request = $request ?: Request::createFromGlobals();
        try {
            $response = $this->kernel->handle($request);
            $response->send();
            $this->kernel->terminate($request, $response);
        } catch (Exception $e) {
            // TODO
        }
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
}
