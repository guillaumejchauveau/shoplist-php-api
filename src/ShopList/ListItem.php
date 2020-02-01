<?php


namespace GECU\ShopList;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use GECU\Rest\ResourceInterface;
use InvalidArgumentException;
use JsonSerializable;
use TypeError;

/**
 * Class ListItem
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
     * @inheritDoc
     */
    public static function getResourceFactory()
    {
        return 'GECU\ShopList\ListItems::getListItem';
    }

    /**
     * @inheritDoc
     */
    public static function getRoutes(): array
    {
        return [
          [
            'method' => 'GET',
            'path' => '/list/{itemId}'
          ],
          [
            'method' => 'PUT',
            'path' => '/list/{itemId}',
            'action' => 'updateWithListItem',
            'requestContentFactory' => 'GECU\ShopList\ListItem::create'
          ],
          [
            'method' => 'DELETE',
            'path' => '/list/{itemId}',
            'action' => 'delete'
          ]
        ];
    }

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

    public function getId(): int
    {
        return $this->id;
    }

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

    public function save(EntityManager $em): self
    {
        $em->persist($this);
        $em->flush();
        return $this;
    }

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
