<?php

use GECU\UK_NSWI142_Project_API\Http\ErrorHandlerInterface;
use GECU\UK_NSWI142_Project_API\Http\Exception\BadRequestException;
use GECU\UK_NSWI142_Project_API\Http\JsonResponse;
use GECU\UK_NSWI142_Project_API\Http\Response;
use GECU\UK_NSWI142_Project_API\Http\Server;
use GECU\UK_NSWI142_Project_API\Http\ServerRequest;
use GECU\UK_NSWI142_Project_API\Http\ServerRequestHandlerInterface;

require_once '../src/autoloader.php';

class App implements ServerRequestHandlerInterface
{

    public function handle(ServerRequest $request): Response
    {
        $data = null;
        try {
            $data = json_decode($request->getBodyContent(), false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new BadRequestException('JSON parse error', null, $e);
        }
        $response = new JsonResponse();
        $response->setStatus(200);
        $response->setData($data);
        return $response;
    }
}

try {
    $server = new Server(new App());
    $server->emit($server->run());
} catch (Throwable $e) {
    Server::crash($e);
}
