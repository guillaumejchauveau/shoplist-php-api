<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


use RuntimeException;

interface ErrorHandlerInterface
{
    public function handle(RuntimeException $exception): Response;
}
