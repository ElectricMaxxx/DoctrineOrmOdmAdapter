<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\ODMAdapter\Mapping\Driver\AnnotationDriver;

class AnnotationDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver()
    {
        $cache = new ArrayCache();
        $reader = new AnnotationReader($cache);

        return new AnnotationDriver($reader);
    }

    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function loadDriverForTestMappingDocuments()
    {
        $annotationDriver = $this->loadDriver();
        $annotationDriver->addPaths(array(__DIR__ . '/Model'));
        return $annotationDriver;
    }
}
