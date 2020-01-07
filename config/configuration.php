<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GECU\Rest\RestResource;
use GECU\ShopList\Item;
use Symfony\Component\DependencyInjection\Definition;

require 'paths.php';

$config = [
  'db' => require ROOT . DS . 'db_config.php',
  'doctrine' => Setup::createAnnotationMetadataConfiguration(
    [APP],
    true,
    null,
    null,
    false
  ),
  'basePath' => '/UK/Web_Applications/Project/API/',
  'resources' => [
    new RestResource('GET', 'items', [Item::class, 'getAllItems']),
    new RestResource('GET', 'items/{id}', [Item::class, 'getItem'])
  ]
];

$entityManagerDefinition = new Definition(
  EntityManager::class,
  [
    $config['db'],
    $config['doctrine']
  ]
);
$entityManagerDefinition->setFactory([EntityManager::class, 'create']);
$config['services'] = [
  'entity_manager' => $entityManagerDefinition
];

return $config;
