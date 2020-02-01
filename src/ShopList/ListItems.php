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
 * Class ListItems
 * @Rest\Route(method="GET", path="/list")
 */
class ListItems implements JsonSerializable
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function getListItem(int $itemId): ListItem
    {
        $item = $this->em->getRepository(Item::class)->find($itemId);
        if ($item === null) {
            throw new InvalidArgumentException('Invalid item ID');
        }
        $listItem = $this->em->getRepository(ListItem::class)->findOneBy(
          [
            'item' => $item
          ]
        );
        if ($listItem === null) {
            throw new InvalidArgumentException('Item is not in the list');
        }
        return $listItem;
    }

    /**
     * @param ListItem $listItem
     * @return ListItem
     * @throws ORMException
     * @throws OptimisticLockException
     * @Rest\Route(
     *     method="POST",
     *     path="/list",
     *     requestContentFactory={ListItem::class, "create"},
     *     status=Response::HTTP_CREATED
     * )
     */
    public function addListItem(ListItem $listItem): ListItem
    {
        if ($this->exists($listItem)) {
            throw new InvalidArgumentException('List item already created');
        }
        return $listItem->save($this->em);
    }

    public function exists(ListItem $listItem): bool
    {
        return $this->em->getRepository(ListItem::class)->count(
            [
              'item' => $listItem->getItem()
            ]
          ) > 0;
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
        return $this->em->getRepository(ListItem::class)->findAll();
    }
}
