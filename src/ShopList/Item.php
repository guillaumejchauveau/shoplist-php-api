<?php


namespace GECU\ShopList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Item
 * @package GECU\ShopList
 * @ORM\Entity
 * @ORM\Table(name="items")
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

    public static function getAllItems(EntityManager $entityManager): array
    {
        return $entityManager->getRepository(static::class)->findAll();
    }

    public static function getItem(EntityManager $entityManager, $id)
    {
        $item = $entityManager->getRepository(static::class)->find($id);
        if ($item === null) {
            throw new NotFoundHttpException("Invalid item ID");
        }
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

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}
