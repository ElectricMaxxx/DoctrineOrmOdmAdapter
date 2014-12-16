<?php

if (!getenv('REPORT_DEPRECATION')) {
    error_reporting(E_ALL ^ E_USER_DEPRECATED);
}


$file = __DIR__.'/../vendor/autoload.php';
if (file_exists($file)) {
    $autoload = require $file;
} else {
    throw new RuntimeException('Install dependencies to run test suite.');
}

use Doctrine\Common\Annotations\AnnotationRegistry;
AnnotationRegistry::registerLoader(array($autoload, 'loadClass'));
AnnotationRegistry::registerFile(__DIR__.'/../lib/Doctrine/ORM/ODMAdapter/Mapping/Annotations/DoctrineAnnotations.php');

// tests are not autoloaded the composer.json
$autoload->add('Doctrine\Tests', __DIR__);
