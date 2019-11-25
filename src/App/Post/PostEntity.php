<?php
declare(strict_types=1);


namespace GECU\ShopList\App\Post;


use GECU\ShopList\Rest\Resource\DocumentInterface;
use GECU\ShopList\Rest\Resource\ResourceInterface;

class PostEntity implements DocumentInterface
{
    public static function fromJsonDecode($json): ResourceInterface
    {
        // TODO: Implement fromJsonDecode() method.
    }

    public static function getResourceName(): ?string
    {
        return null;
    }

    public static function getParentResources(): array
    {
        return [PostTable::class];
    }

    /**
     * Specify data which should be serialized to JSON
     * @link https://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        // TODO: Implement jsonSerialize() method.
    }
}
