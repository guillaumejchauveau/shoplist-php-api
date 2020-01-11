<?php


namespace GECU\Rest;


interface ResourceInterface
{
    public static function getResourceFactory(): Callable;

    /**
     * @return array<array|Route>
     */
    public static function getRoutes(): array;
}
