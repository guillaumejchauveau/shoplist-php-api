<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GECU\Rest;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

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

    /**
     * Adds an item to the database.
     * @param Item $item
     * @return Item
     * @throws ORMException
     * @throws OptimisticLockException
     * @Rest\Route(
     *     method="POST",
     *     path="/items",
     *     requestContentFactory={Item::class, "create"},
     *     status=Response::HTTP_CREATED
     * )
     */
    public function addItem(Item $item): Item
    {
        if ($this->em->getRepository(Item::class)->count(
            [
              'name' => $item->getName()
            ]
          ) > 0) {
            throw new InvalidArgumentException('Item already exists');
        }
        return $item->save($this->em);
    }
}
