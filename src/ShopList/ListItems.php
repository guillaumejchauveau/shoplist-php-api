<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use GECU\Rest\ResourceInterface;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

class ListItems implements ResourceInterface, JsonSerializable
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
        return 'GECU\ShopList\ListItems::__construct';
    }

    /**
     * @inheritDoc
     */
    public static function getRoutes(): array
    {
        return [
          [
            'method' => 'GET',
            'path' => '/list'
          ],
          [
            'method' => 'POST',
            'path' => '/list',
            'action' => 'addListItem',
            'requestContentFactory' => 'GECU\ShopList\ListItem::create',
            'status' => Response::HTTP_CREATED
          ]
        ];
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
