<?php


namespace GECU\Rest;


use Doctrine\Common\Annotations\Annotation\Target;
use GECU\Rest\Helper\FactoryHelper;

/**
 * Annotates the factory of a resource. If placed on the resource's class, the
 * annotation should have a value holding a pseudo callable. It should not have
 * a value if placed on a method, as the method is considered to be the actual
 * factory.
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 * @see FactoryHelper
 */
class ResourceFactory
{
    public $value;
}
