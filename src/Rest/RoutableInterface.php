<?php


namespace GECU\Rest;


/**
 * A trait that allows a resource's routes to be retrieved.
 */
interface RoutableInterface
{
    /**
     * @return iterable An iterable containing {@see Route} instances or arrays
     *  describing a route
     * @see Route::fromArray()
     */
    public static function getRoutes(): iterable;
}
