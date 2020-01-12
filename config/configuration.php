<?php

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use GECU\ShopList\Item;
use GECU\ShopList\Items;
use GECU\ShopList\ListItem;
use GECU\ShopList\ListItems;
use Symfony\Component\DependencyInjection\Definition;

require 'paths.php';

$config = [
  'db' => require ROOT . DS . 'db_config.php',
  'dev' => true,
  'resources' => [
    Items::class,
    Item::class,
    ListItems::class,
    ListItem::class
  ]
];

$config['doctrine'] = Setup::createAnnotationMetadataConfiguration(
  [APP],
  $config['dev'],
  null,
  null,
  false
);

$config['services'] = [
  (new Definition(
    EntityManager::class,
    [
      $config['db'],
      $config['doctrine']
    ]
  ))->setFactory([EntityManager::class, 'create'])
];

return $config;
