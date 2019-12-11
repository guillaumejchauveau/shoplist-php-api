<?php

use GECU\ShopList\Kernel\ErrorController;
use GECU\ShopList\Kernel\Router;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernel;

require_once '../vendor/autoload.php';


$request = Request::createFromGlobals();
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new Router());
$dispatcher->addSubscriber(new ErrorListener([new ErrorController(), "handle"]));
$kernel = new HttpKernel($dispatcher, new ControllerResolver(), new RequestStack(), new ArgumentResolver());

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
