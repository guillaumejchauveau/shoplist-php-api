<?php


namespace GECU\ShopList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use GECU\Rest;
use JsonSerializable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Item
 * @package GECU\ShopList
 * @ORM\Entity
 * @ORM\Table(name="items")
 * @Rest\Route(method="GET", path="/items/{id}")
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
     * @param int|null $id
     * @return Item|object|null
     * @Rest\ResourceFactory
     */
    public static function createResource(EntityManager $em, int $id = null)
    {
        if ($id === null) {
            return new self($em);
        }
        $item = $em->getRepository(self::class)->find($id);
        if ($item === null) {
            throw new NotFoundHttpException('Invalid item ID');
        }
        $item->attachEntityManager($em);
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

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
