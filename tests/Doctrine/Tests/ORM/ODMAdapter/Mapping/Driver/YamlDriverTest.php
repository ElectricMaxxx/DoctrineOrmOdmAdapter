<?php


namespace Doctrine\Tests\ORM\ODMAdapter\Mapping\Driver;

use Doctrine\ORM\ODMAdapter\Mapping\Driver\YamlDriver;

class YamlDriverTest extends AbstractMappingDriverTest
{
    /**
     * @return YamlDriver
     */
    protected function loadDriver()
    {
        $location = __DIR__ . '/Model/yml';

        return new YamlDriver($location);
    }

    protected function loadDriverForTestMappingDocuments()
    {
        return $this->loadDriver();
    }
}
