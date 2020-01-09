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
  'doctrine' => Setup::createAnnotationMetadataConfiguration(
    [APP],
    false,
    null,
    null,
    false
  ),
  'basePath' => '/UK/Web_Applications/Project/API/',
  'resources' => [
    Items::class,
    Item::class,
    ListItems::class,
    ListItem::class
  ]
];

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
