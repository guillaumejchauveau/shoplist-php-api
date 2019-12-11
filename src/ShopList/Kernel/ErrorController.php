<?php


namespace GECU\ShopList\Kernel;


use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ErrorController
{
    public function handle(Throwable $exception): Response
    {
        return new JsonResponse(
          [
            'error' => $exception->getMessage()
          ]
        );
    }

}