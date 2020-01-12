<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use GECU\Rest\ResourceInterface;
use JsonSerializable;

class Items implements ResourceInterface, JsonSerializable
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

    public static function getResourceFactory(): callable
    {
        return [self::class, 'createResource'];
    }

    public static function getRoutes(): array
    {
        return [
          [
            'method' => 'GET',
            'path' => '/items'
          ]
        ];
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
