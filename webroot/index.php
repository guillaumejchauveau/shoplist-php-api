<?php

use GECU\Rest\Kernel\ErrorController;
use GECU\Rest\Kernel\Router;
use GECU\Rest\Kernel\ServiceArgumentValueResolver;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernel;

require dirname(__DIR__) . '/vendor/autoload.php';
$configuration = require dirname(__DIR__) . '/config/configuration.php';

$container = new ContainerBuilder();
$services = require CONFIG . 'services.php';
foreach ($services as $id => $definition) {
    $container->setDefinition($id, $definition);
    $container->setAlias($definition->getClass(), new Alias($id));
}

$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new Router());
$dispatcher->addSubscriber(new ErrorListener([new ErrorController(), 'handle']));

$argumentValueResolvers = ArgumentResolver::getDefaultArgumentValueResolvers();
$argumentValueResolvers[] = new ServiceArgumentValueResolver($container);
$kernel = new HttpKernel(
  $dispatcher,
  new ControllerResolver(),
  new RequestStack(),
  new ArgumentResolver(null, $argumentValueResolvers)
);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
