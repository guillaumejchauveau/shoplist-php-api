<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use GECU\Rest;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Class Items
 * @package GECU\ShopList
 * @Rest\Route(method="GET", path="/items")
 */
class Items implements JsonSerializable
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public static function getResourceFactory()
    {
        return 'GECU\ShopList\Items::__construct';
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->getCollection();
    }

    public function getCollection(): array
    {
        return $this->em->getRepository(Item::class)->findAll();
    }

    public function getItem(int $id): Item
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        if ($item === null) {
            throw new InvalidArgumentException('Invalid item ID');
        }
        return $item;
    }
}
