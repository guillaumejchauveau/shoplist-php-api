<?php


namespace GECU\Rest\Kernel;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class Router implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
          KernelEvents::REQUEST => 'onRequest'
        ];
    }

    public function onRequest(RequestEvent $event)
    {
        if (!$event->getRequest()->attributes->has('_controller')) {
            $event->getRequest()->attributes->set(
              '_controller',
              [
                new Controller(),
                "respond"
              ]
            );
        }
    }
}
