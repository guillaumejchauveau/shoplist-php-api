<?php


namespace GECU\Rest\Kernel;


use Exception;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class RequestContentAsResourceArgumentValueResolver implements ArgumentValueResolverInterface
{

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument)
    {
        $resourceClass = $request->attributes->get(Api::REQUEST_ATTRIBUTE_REQUEST_CONTENT_CLASS);
        if ($resourceClass === null) {
            return false;
        }
        return $argument->getType() === $resourceClass;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument)
    {
        try {
            $resourceClass = new ReflectionClass($argument->getType());
            $resourceInstance = $resourceClass->newInstanceWithoutConstructor();
            $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
            foreach ($data as $key => $value) {
                if (!$resourceClass->hasProperty($key)) {
                    throw new Exception(sprintf('Invalid property "%s"', $key));
                }
                $property = $resourceClass->getProperty($key);
                $property->setAccessible(true);
                $property->setValue($resourceInstance, $value);
            }
            yield $resourceInstance;
        } catch (ReflectionException $e) {
            throw new RuntimeException('Unexpected invalid resolution arguments');
        } catch (Throwable $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }
}
