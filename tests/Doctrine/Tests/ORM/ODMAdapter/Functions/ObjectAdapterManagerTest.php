<?php

namespace Doctrine\Tests\ORM\ODMAdapter\Functions;

use Doctrine\ORM\ODMAdapter\ObjectAdapterManager;
use Doctrine\ORM\ODMAdapter\Reference;

class ObjectAdapterManagerTest extends BaseFunctionalTestCase
{
    public function testGetMangerByType()
    {
        $this->markTestSkipped('Wil do later');
        #$this->assertEquals($this->dm, $this->objectAdapterManager->getManagerByType(Reference::PHPCR));
    }
}
