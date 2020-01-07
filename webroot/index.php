<?php

use GECU\Rest\Kernel\Api;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require dirname(__DIR__) . '/vendor/autoload.php';
$configuration = require dirname(__DIR__) . '/config/configuration.php';

$container = new ContainerBuilder();
foreach ($configuration['services'] as $id => $definition) {
    $container->setDefinition($id, $definition);
    $container->setAlias($definition->getClass(), new Alias($id));
}

$api = new Api($configuration['basePath'], $configuration['resources'], $container);
$api->run();
