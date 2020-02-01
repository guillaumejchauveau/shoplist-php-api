<?php


namespace GECU\Rest\Helper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * A simple argument value resolver that uses an associative array.
 */
class MapValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var array
     */
    protected $map;

    /**
     * MapValueResolver constructor.
     * @param array $map The initial associative array
     */
    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

    /**
     * Updates the resolver's associative array.
     * @param array $map The new associative array
     */
    public function setMap(array $map): void
    {
        $this->map = $map;
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        return array_key_exists($argument->getName(), $this->map);
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        yield $this->map[$argument->getName()];
    }
}
