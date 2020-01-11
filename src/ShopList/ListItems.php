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
        $this->attachEntityManager($em);
    }

    public function attachEntityManager(EntityManager $em): void
    {
        $this->em = $em;
    }

    public static function createResource(EntityManager $em): self
    {
        return new self($em);
    }

    public static function getResourceFactory(): Callable
    {
        return [self::class, 'createResource'];
    }

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
            'requestContentClass' => ListItem::class,
            'status' => Response::HTTP_CREATED
          ]
        ];
    }

    public function addListItem(ListItem $listItem): ListItem
    {
        $listItem->attachEntityManager($this->em);
        $listItem->updateItem();
        if ($this->exists($listItem)) {
            throw new InvalidArgumentException('List item already created');
        }
        $this->em->persist($listItem);
        $this->em->flush();
        return $listItem;
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
