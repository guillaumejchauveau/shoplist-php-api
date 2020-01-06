<?php


namespace GECU\Rest;

use Doctrine\Common\Annotations\Annotation\Attribute;
use Doctrine\Common\Annotations\Annotation\Attributes;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * Class Resource
 * @package GECU\Rest
 * @Annotation
 * @Target("CLASS")
 * @Attributes({
 *     @Attribute("name", type = "string")
 *     })
 */
class Resource
{
    /**
     * @var string
     */
    protected $name;

    public function __construct(array $values)
    {
        $this->name = $values['name'];
    }
}
