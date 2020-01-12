<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use GECU\Rest;
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
}
