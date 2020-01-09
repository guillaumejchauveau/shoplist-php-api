<?php

use GECU\Rest\Kernel\Api;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require dirname(__DIR__) . '/vendor/autoload.php';
$configuration = require dirname(__DIR__) . '/config/configuration.php';

// Add service definitions from the configuration for dependency injection.
$container = new ContainerBuilder();
foreach ($configuration['services'] as $id => $definition) {
    $container->setDefinition($definition->getClass(), $definition);
}

$api = new Api($configuration['basePath'], $configuration['resources'], $container);
$api->run();
