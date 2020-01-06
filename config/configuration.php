<?php

use Doctrine\ORM\Tools\Setup;

require 'paths.php';

return [
  'db' => require ROOT . DS . 'db_config.php',
  'doctrine' => Setup::createAnnotationMetadataConfiguration(
    [APP],
    true,
    null,
    null,
    false
  )
];
