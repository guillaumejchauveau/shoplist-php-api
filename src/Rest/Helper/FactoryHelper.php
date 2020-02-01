<?php


namespace GECU\Rest\Helper;


use Closure;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;

class FactoryHelper
{
    public static function getFactoryReturnType($factory): ?string
    {
        try {
            if (is_array($factory)) {
                $reflection = new ReflectionMethod($factory[0], $factory[1]);
            } elseif (is_object($factory) && !$factory instanceof Closure) {
                $reflection = (new ReflectionObject($factory))->getMethod('__invoke');
            } else {
                $reflection = new ReflectionFunction($factory);
            }
        } catch (ReflectionException $e) {
            throw new InvalidArgumentException('Invalid factory', 0, $e);
        }
        $returnTypeReflexion = $reflection->getReturnType();
        if (!$returnTypeReflexion instanceof ReflectionNamedType) {
            return null;
        }
        $returnType = $returnTypeReflexion->getName();
        if ($returnType === 'self') {
            if ($factory instanceof Closure) {
                $returnType = Closure::class;
            } elseif (is_array($factory) || is_object($factory)) {
                $returnType = $reflection->getDeclaringClass()->getName();
            } else {
                return null;
            }
        }
        return $returnType;
    }

    public static function invokeFactory(
      $factory,
      Request $request,
      ArgumentResolver $argumentResolver
    ) {
        $factoryArgs = $argumentResolver->getArguments(
          $request,
          $factory
        );
        if (!is_array($factory)) {
            return $factory(...$factoryArgs);
        }
        try {
            $reflection = new ReflectionMethod($factory[0], $factory[1]);
            if ($reflection->isConstructor()) {
                return $reflection->getDeclaringClass()->newInstanceArgs(
                  $factoryArgs
                );
            }
            if (!$reflection->isStatic() && !is_object($factory[0])) {
                $resourceFactoryConstructorArgs = $argumentResolver->getArguments(
                  $request,
                  [$factory[0], '__construct']
                );
                $factoryClassReflection = new ReflectionClass($factory[0]);
                $factory[0] = $factoryClassReflection->newInstanceArgs(
                  $resourceFactoryConstructorArgs
                );
            }
            return $factory(...$factoryArgs);
        } catch (ReflectionException $e) {
            throw new RuntimeException('Invocation failed', 0, $e);
        }
    }
}
