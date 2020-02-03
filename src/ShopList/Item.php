<?php


namespace GECU\ShopList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GECU\Rest;
use JsonSerializable;

/**
 * Represents an item available in the shop list.
 * @ORM\Entity
 * @ORM\Table(name="items")
 * @Rest\Route(method="GET", path="/items/{id}")
 * @Rest\ResourceFactory({Items::class, "getItem"})
 */
class Item implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    protected $id;
    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * Creates a new item with the given properties.
     * @param string $name
     * @return Item
     */
    public static function create(string $name): self
    {
        $item = new Item();
        $item->setName($name);
        return $item;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
          'id' => $this->getId(),
          'name' => $this->getName()
        ];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Item
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Persists the item in the database.
     * @param EntityManager $em
     * @return Item
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(EntityManager $em): self
    {
        $em->persist($this);
        $em->flush();
        return $this;
    }
}
