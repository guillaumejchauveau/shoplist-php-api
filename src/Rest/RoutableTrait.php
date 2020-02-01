<?php


namespace GECU\Rest;


/**
 * A trait that allows a resource's routes to be retrieved.
 */
trait RoutableTrait
{
    /**
     * @return iterable An iterable containing {@link Route} instances or arrays
     *  describing a route
     * @see Route::fromArray()
     */
    abstract public static function getRoutes(): iterable;
}
