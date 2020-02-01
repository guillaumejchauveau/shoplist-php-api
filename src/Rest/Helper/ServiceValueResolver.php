<?php


namespace GECU\Rest\Helper;


use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * An argument value resolver that uses a service container.
 */
class ServiceValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * ServiceValueResolver constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $this->container->has($argument->getType());
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        yield $this->container->get($argument->getType());
    }
}
