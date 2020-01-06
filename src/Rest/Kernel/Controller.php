<?php


namespace GECU\Rest\Kernel;


use Doctrine\ORM\EntityManager;
use JsonException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Controller
{
    public function respond(Request $request, EntityManager $entityManager): Response
    {
        try {
            $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
            return new JsonResponse($data);
        } catch (JsonException $e) {
            throw new BadRequestHttpException('JSON parse error');
        }
    }
}
