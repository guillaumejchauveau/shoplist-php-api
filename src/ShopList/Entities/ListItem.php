<?php


namespace GECU\ShopList;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ListItem
 * @package GECU\ShopList
 * @ORM\Entity
 * @ORM\Table(name="list")
 */
class ListItem
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
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $quantity;
    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $order;

    public function getId()
    {
        return $this->id;
    }

    public function getItemId()
    {
        return $this->item->getId();
    }

}
