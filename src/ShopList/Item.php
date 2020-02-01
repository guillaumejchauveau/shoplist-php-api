<?php


namespace GECU\ShopList;

use Doctrine\ORM\Mapping as ORM;
use GECU\Rest\ResourceInterface;
use JsonSerializable;

/**
 * Class Item
 * @ORM\Entity
 * @ORM\Table(name="items")
 */
class Item implements ResourceInterface, JsonSerializable
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
    public static function getResourceFactory()
    {
        return 'GECU\ShopList\Items::getItem';
    }

    /**
     * @inheritDoc
     */
    public static function getRoutes(): array
    {
        return [
          [
            'method' => 'GET',
            'path' => '/items/{id}'
          ]
        ];
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
