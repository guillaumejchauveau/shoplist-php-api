<?php


namespace GECU\ShopList;

use Doctrine\ORM\Mapping as ORM;
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
}
