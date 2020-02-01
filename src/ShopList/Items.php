<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use GECU\Rest;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Represents the collection of available items in the shop list.
 * @Rest\Route(method="GET", path="/items")
 */
class Items implements JsonSerializable
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Items constructor.
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return $this->getCollection();
    }

    /**
     * @return array
     */
    public function getCollection(): array
    {
        return $this->em->getRepository(Item::class)->findAll();
    }

    /**
     * Retrieves an item given its ID.
     * @param int $id
     * @return Item
     */
    public function getItem(int $id): Item
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        if ($item === null) {
            throw new InvalidArgumentException('Invalid item ID');
        }
        return $item;
    }
}
