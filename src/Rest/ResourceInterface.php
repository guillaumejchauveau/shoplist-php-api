<?php


namespace GECU\Rest;


interface ResourceInterface
{
    public static function getResourceConstructor(): Callable;

    /**
     * @return array<array|ResourceRoute>
     */
    public static function getRoutes(): array;
}
