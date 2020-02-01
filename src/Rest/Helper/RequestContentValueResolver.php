<?php


namespace GECU\Rest\Helper;


use GECU\Rest\Kernel\RestRequest;
use JsonException;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\DefaultValueResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * An argument value resolver that uses the request's route's request content
 * factory. The factory's arguments are resolved using an internal argument
 * resolver instance.
 */
class RequestContentValueResolver implements ArgumentValueResolverInterface
{
    /**
     * @var ArgumentResolver
     */
    protected $argumentResolver;
    /**
     * @var MapValueResolver
     */
    protected $mapValueResolver;

    /**
     * RequestContentValueResolver constructor.
     * @param ArgumentValueResolverInterface[] $additionalArgumentValueResolvers
     *  Value resolvers that will be added to the internal argument resolver
     */
    public function __construct(iterable $additionalArgumentValueResolvers = [])
    {
        $this->mapValueResolver = new MapValueResolver();
        $argumentValueResolvers = [$this->mapValueResolver];
        array_push($argumentValueResolvers, ...$additionalArgumentValueResolvers);
        $argumentValueResolvers[] = new DefaultValueResolver();
        $this->argumentResolver = new ArgumentResolver(null, $argumentValueResolvers);
    }

    /**
     * @inheritDoc
     */
    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if (!$request instanceof RestRequest) {
            return false;
        }
        $requestContentType = $request->getRoute()->getRequestContentType();
        if ($requestContentType === null) {
            return false;
        }
        return $argument->getType() === $requestContentType;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (!$request instanceof RestRequest) {
            throw new LogicException('Unexpected invalid request');
        }
        try {
            $this->mapValueResolver->setMap(
              json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)
            );
        } catch (JsonException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        $requestContentFactory = $request->getRoute()->getRequestContentFactory();
        yield FactoryHelper::invokeFactory(
          $requestContentFactory,
          $request,
          $this->argumentResolver
        );
    }
}
