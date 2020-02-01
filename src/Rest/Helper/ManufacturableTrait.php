<?php


namespace GECU\Rest\Helper;


/**
 * A trait for objects capable of providing a factory for themselves.
 */
trait ManufacturableTrait
{
    /**
     * @return mixed|null The factory as a pseudo callable
     * @see FactoryHelper
     */
    abstract public static function getFactory();
}
