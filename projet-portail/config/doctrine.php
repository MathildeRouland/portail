<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../'); 
$dotenv->load();

$dbParams = [
    'url' => getenv('DATABASE_URL'), 
];

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$isDevMode = true;
$paths = ['src/Entity'];  

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);

$entityManager = EntityManager::create($dbParams, $config);