<?php


namespace GECU\ShopList;

use Doctrine\ORM\Mapping as ORM;
use GECU\Rest;
use JsonSerializable;

/**
 * Class Item
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
