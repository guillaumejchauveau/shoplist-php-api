<?php


namespace GECU\Rest\Kernel;


use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class ServiceArgumentValueResolver implements ArgumentValueResolverInterface
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return $this->container->has($argument->getType());
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->container->get($argument->getType());
    }
}
