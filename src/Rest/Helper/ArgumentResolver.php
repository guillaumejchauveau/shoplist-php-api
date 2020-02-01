<?php

/*
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace GECU\Rest\Helper;


use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver as SymfonyArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactory;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadataFactoryInterface;

/**
 * Responsible for resolving the arguments passed to a (pseudo) callable.
 *
 * This modified version of {@see \Symfony\Component\HttpKernel\Controller\ArgumentResolver} allows
 * the resolution of constructor arguments.
 *
 * @author Iltar van der Berg <kjarli@gmail.com>
 */
final class ArgumentResolver
{
    private $argumentMetadataFactory;

    /**
     * @var ArgumentValueResolverInterface[]
     */
    private $argumentValueResolvers;

    public function __construct(
      ArgumentMetadataFactoryInterface $argumentMetadataFactory = null,
      iterable $argumentValueResolvers = []
    ) {
        $this->argumentMetadataFactory = $argumentMetadataFactory ?: new ArgumentMetadataFactory();
        $this->argumentValueResolvers = $argumentValueResolvers ?: self::getDefaultArgumentValueResolvers(
        );
    }

    public static function getDefaultArgumentValueResolvers(): iterable
    {
        return SymfonyArgumentResolver::getDefaultArgumentValueResolvers();
    }

    public function getArguments(Request $request, $callable): array
    {
        $arguments = [];

        foreach ($this->argumentMetadataFactory->createArgumentMetadata($callable) as $metadata) {
            foreach ($this->argumentValueResolvers as $resolver) {
                if (!$resolver->supports($request, $metadata)) {
                    continue;
                }

                $resolved = $resolver->resolve($request, $metadata);

                $atLeastOne = false;
                foreach ($resolved as $append) {
                    $atLeastOne = true;
                    $arguments[] = $append;
                }

                if (!$atLeastOne) {
                    throw new InvalidArgumentException(
                      sprintf('%s::resolve() must yield at least one value.', get_class($resolver))
                    );
                }

                // continue to the next callable argument
                continue 2;
            }

            $representative = $callable;

            if (is_array($representative)) {
                $representative = sprintf(
                  '%s::%s()',
                  get_class($representative[0]),
                  $representative[1]
                );
            } elseif (is_object($representative)) {
                $representative = get_class($representative);
            }

            throw new RuntimeException(
              sprintf(
                'Callable "%s" requires that you provide a value for the "$%s" argument. Either the argument is nullable and no null value has been provided, no default value has been provided or because there is a non optional argument after this one.',
                $representative,
                $metadata->getName()
              )
            );
        }

        return $arguments;
    }
}
