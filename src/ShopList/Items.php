<?php


namespace GECU\ShopList;


use Doctrine\ORM\EntityManager;
use GECU\Rest\ResourceInterface;
use InvalidArgumentException;
use JsonSerializable;

class Items implements ResourceInterface, JsonSerializable
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
        return [self::class, '__construct'];
    }

    /**
     * @inheritDoc
     */
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

    public function getItem(int $id): Item
    {
        $item = $this->em->getRepository(Item::class)->find($id);
        if ($item === null) {
            throw new InvalidArgumentException('Invalid item ID');
        }
        return $item;
    }
}
