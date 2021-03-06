<?php


namespace GECU\ShopList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use GECU\Rest;
use InvalidArgumentException;
use JsonSerializable;
use TypeError;

/**
 * Represents an item added to the shop list.
 * @ORM\Entity
 * @ORM\Table(name="list")
 * @Rest\Route(method="GET", path="/list/{itemId}")
 * @Rest\ResourceFactory({ListItems::class, "getListItem"})
 */
class ListItem implements JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    protected $id;
    /**
     * @ORM\OneToOne(targetEntity="Item")
     * @ORM\JoinColumn(nullable=false)
     * @var Item
     */
    protected $item;
    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $amount;
    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position;

    /**
     * Creates a new list item with the given properties.
     * @param EntityManager $em
     * @param int $itemId
     * @param int $amount
     * @param int $position
     * @return ListItem
     */
    public static function create(
      EntityManager $em,
      int $itemId,
      int $amount,
      int $position
    ): self {
        $listItem = new self();
        $listItem->setItem($em->getRepository(Item::class)->find($itemId));
        $listItem->setAmount($amount);
        $listItem->setPosition($position);
        return $listItem;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Updates the list item with the properties of a given list item.
     * @param EntityManager $em
     * @param ListItem $listItem
     * @return ListItem
     * @throws ORMException
     * @throws OptimisticLockException
     * @Rest\Route(
     *     method="PUT",
     *     path="/list/{itemId}",
     *     requestContentFactory={ListItem::class, "create"}
     * )
     */
    public function updateWithListItem(EntityManager $em, ListItem $listItem): self
    {
        if ($listItem->getItem()->getId() !== $this->getItem()->getId()) {
            throw new InvalidArgumentException('Item ID cannot be updated');
        }
        try {
            $this->setPosition($listItem->getPosition());
            $this->setAmount($listItem->getAmount());
        } catch (TypeError $e) {
            throw new InvalidArgumentException('Invalid list item', 0, $e);
        }
        return $this->save($em);
    }

    /**
     * @return Item
     */
    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @param Item $item
     * @return ListItem
     */
    public function setItem(Item $item): self
    {
        $this->item = $item;
        return $this;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return ListItem
     */
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     * @return ListItem
     */
    public function setAmount(int $amount): self
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }
        $this->amount = $amount;
        return $this;
    }

    /**
     * Persists the list item in the database.
     * @param EntityManager $em
     * @return ListItem
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(EntityManager $em): self
    {
        $em->persist($this);
        $em->flush();
        return $this;
    }

    /**
     * Removes the list item from the database.
     * @param EntityManager $em
     * @throws ORMException
     * @throws OptimisticLockException
     * @Rest\Route(method="DELETE", path="/list/{itemId}")
     */
    public function delete(EntityManager $em): void
    {
        $em->remove($this);
        $em->flush();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
          'itemId' => $this->getItem()->getId(),
          'amount' => $this->getAmount(),
          'position' => $this->getPosition()
        ];
    }
}
