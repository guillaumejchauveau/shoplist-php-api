<?php


namespace GECU\Rest\Helper;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class MapValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var array
     */
    protected $map;

    public function __construct(array $map = [])
    {
        $this->map = $map;
    }

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
