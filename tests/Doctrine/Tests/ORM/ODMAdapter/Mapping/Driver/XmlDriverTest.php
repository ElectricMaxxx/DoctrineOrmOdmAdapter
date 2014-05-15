<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver;


use Doctrine\ORM\ODMAdapter\Mapping\Driver\XmlDriver;

class XmlDriverTest extends AbstractMappingDriverTest {

    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function loadDriver()
    {
        $location = __DIR__.'/Model/xml';

        return new XmlDriver($location);
    }

    /**
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function loadDriverForTestMappingDocuments()
    {
        return $this->loadDriver();
    }
}
