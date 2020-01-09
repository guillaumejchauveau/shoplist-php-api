<?php


namespace GECU\ShopList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use GECU\Rest\ResourceInterface;
use InvalidArgumentException;
use JsonSerializable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ListItem
 * @package GECU\ShopList
 * @ORM\Entity
 * @ORM\Table(name="list")
 */
class ListItem implements ResourceInterface, JsonSerializable
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
     * @var Item
     */
    protected $item;
    /**
     * @var int
     */
    protected $itemId;
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

    public static function createResource(EntityManager $em, int $itemId = null)
    {
        if ($itemId === null) {
            return new self($em);
        }
        $item = $em->getRepository(Item::class)->find($itemId);
        if ($item === null) {
            throw new NotFoundHttpException('Invalid item ID');
        }
        $listItem = $em->getRepository(self::class)->findOneBy(
          [
            'item' => $item
          ]
        );
        if ($listItem === null) {
            throw new NotFoundHttpException('Item is not in the list');
        }
        $listItem->attachEntityManager($em);
        return $listItem;
    }

    public static function getResourceConstructor(): Callable
    {
        return [self::class, 'createResource'];
    }

    public static function getRoutes(): array
    {
        return [
          [
            'method' => 'PUT',
            'path' => '/list/{itemId}',
            'action' => 'updateWithListItem',
            'requestContentClass' => ListItem::class,
          ],
          [
            'method' => 'DELETE',
            'path' => '/list/{itemId}',
            'action' => 'delete'
          ]
        ];
    }

    public function getId()
    {
        return $this->id;
    }

    public function getItem(): Item
    {
        return $this->item;
    }

    /**
     * @param Item $item
     */
    public function setItem(?Item $item): void
    {
        if ($item === null) {
            throw new InvalidArgumentException('Invalid item');
        }
        $this->item = $item;
        $this->itemId = $item->getId();
    }

    public function updateWithListItem(ListItem $listItem): self
    {
        $listItem->attachEntityManager($this->em);
        $listItem->updateItem();
        if ($listItem->getItemId() !== $this->getItemId()) {
            throw new InvalidArgumentException('Item ID cannot be updated');
        }
        $this->setPosition($listItem->getPosition());
        $this->setAmount($listItem->getAmount());
        $this->em->persist($this);
        $this->em->flush();
        return $this;
    }

    public function updateItem(): void
    {
        $this->setItem($this->em->getRepository(Item::class)->find($this->itemId));
    }

    public function getItemId()
    {
        return $this->item->getId();
    }

    public function setItemId(int $itemId): void
    {
        $this->itemId = $itemId;
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
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
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
     */
    public function setAmount(int $amount): void
    {
        if ($amount < 1) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }
        $this->amount = $amount;
    }

    public function delete(): void
    {
        $this->em->remove($this);
        $this->em->flush();
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
          'itemId' => $this->getItemId(),
          'amount' => $this->getAmount(),
          'position' => $this->getPosition()
        ];
    }
}
