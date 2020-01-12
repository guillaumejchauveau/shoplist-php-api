<?php


namespace GECU\Rest;


/**
 * Methods for resource information retrieval.
 * @package GECU\Rest
 */
interface ResourceInterface
{
    /**
     * Returns a function for creating a new instance of the resource.
     * @return callable
     */
    public static function getResourceFactory(): callable;

    /**
     * Returns all the routes for the resource.
     * @return array<array|Route> An array containing either a Route instance or
     * an array describing a Route instance
     * @see Route::fromArray()
     */
    public static function getRoutes(): array;
}
