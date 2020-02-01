<?php


namespace GECU\Rest;


/**
 * Methods for resource information retrieval.
 */
interface ResourceInterface
{
    /**
     * Returns a function for creating a new instance of the resource. Can be a
     * constructor.
     * @return callable
     */
    public static function getResourceFactory();

    /**
     * Returns all the routes for the resource.
     * @return iterable An iterable containing either a Route
     *  instance or an array describing a Route instance
     * @see Route::fromArray()
     */
    public static function getRoutes(): iterable;
}
