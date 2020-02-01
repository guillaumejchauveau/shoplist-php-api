<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Console\ConsoleRunner;

require __DIR__ . '/vendor/autoload.php';
$configuration = require __DIR__ . '/config/configuration.php';


AnnotationRegistry::registerFile(REST . '/RestAnnotations.php');
$entityManager = EntityManager::create($configuration['db'], $configuration['doctrine']);
return ConsoleRunner::createHelperSet($entityManager);
