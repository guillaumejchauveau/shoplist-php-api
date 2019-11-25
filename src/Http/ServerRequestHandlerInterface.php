<?php
declare(strict_types=1);


namespace GECU\ShopList\Http;


interface ServerRequestHandlerInterface
{
    public function handle(ServerRequest $request): Response;
}
