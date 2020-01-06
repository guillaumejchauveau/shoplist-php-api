<?php


use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Definition;

$entityManagerDefinition = new Definition(
  EntityManager::class,
  [
    $configuration['db'],
    $configuration['doctrine']
  ]
);
$entityManagerDefinition->setFactory([EntityManager::class, 'create']);

return [
  'entity_manager' => $entityManagerDefinition
];
