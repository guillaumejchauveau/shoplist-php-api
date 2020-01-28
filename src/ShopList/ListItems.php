<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GECU\Rest;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;
use TypeError;

/**
 * Class ListItems
 * @package GECU\ShopList
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
        $this->attachEntityManager($em);
    }

    public function attachEntityManager(EntityManager $em): void
    {
        $this->em = $em;
    }

    /**
     * @param EntityManager $em
     * @return static
     * @Rest\ResourceFactory
     */
    public static function createResource(EntityManager $em): self
    {
        return new self($em);
    }

    /**
     * @param ListItem $listItem
     * @return ListItem
     * @throws ORMException
     * @throws OptimisticLockException
     * @Rest\Route(method="POST", path="/list", requestContentClass=ListItem::class, status=Response::HTTP_CREATED)
     */
    public function addListItem(ListItem $listItem): ListItem
    {
        $listItem->attachEntityManager($this->em);
        try {
            $listItem->refresh();
        } catch (TypeError $e) {
            throw new InvalidArgumentException('Invalid list item', 0, $e);
        }
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
